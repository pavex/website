# Building Websites with pavex/website

A practical guide to building PHP websites using the `pavex/website` framework.

---

## Overview

`pavex/website` is a minimalist PHP framework built around three concepts:

- **Router** — matches incoming URLs to presenters
- **Presenter** — prepares data and renders a template
- **Control** — wires everything together and sends the response

The entry point is `index.php` → `Application` → `ApplicationContainer` → `Control::run()`.

---

## Project Structure

```
website-v1/
  index.php                        — entry point
  config.php                       — production config (returns array)
  config-dev.php                   — local dev overrides (gitignored)
  src/
    Website/
      Application.php              — boots ApplicationContainer, calls Control::run()
      ApplicationContainer.php     — lazy service locator (request, response, router, services)
      Presenter/
        AbstractPresenter.php      — link(), validatePresenterUrl(), nocache(), noindex()
        BasePresenter.php          — PageContext, render() with cache/noindex headers
        HomePresenter.php          — example presenter
        templates/
          base.php                 — HTML layout (includes header.php, footer.php)
          header.php
          footer.php
          home.php                 — child template
      Router/
        WebsiteRules.php           — defines all InputRules and OutputRules
  vendor-packages/                 — local editable packages (symlinked into vendor/)
```

---

## Boot Sequence

```
index.php
  └── Application::__construct($env)        loads config.php
        └── ApplicationContainer($env)
  └── Application::run()
        └── ApplicationContainer::getControl()->run()
              ├── Router::getActionHandler($request)   matches URL → ActionHandler
              ├── Control::renderPresenter($handler)   creates Presenter, calls __toString()
              └── HttpResponse::send()
```

---

## Adding a New Page

### 1. Create the Presenter

The presenter lifecycle method is **`render()`** — there is no `init()` in pavex/website.
`render()` must always end with `return parent::render()`, which triggers the template engine.

```php
// src/Website/Presenter/AboutPresenter.php
namespace App\Website\Presenter;

final class AboutPresenter extends BasePresenter
{
    // public — injected by Control from route args (none here, but shown for completeness)
    public string $section = '';

    // protected — prepared in render(), readable in the template via $this->
    protected array $team = [];
    protected string $email = '';

    // private — internal helper, not accessible in the template
    private string $contactPrefix = 'mailto:';


    public function render(): ?string          // always render(), never init()
    {
        $this->context->title = 'About — My Site';
        $this->context->description = 'Learn more about us.';
        $this->context->lang = 'en';
        $this->context->canonical = $this->link($this);  // always use router, never hardcode

        // private property used internally to build data for the template
        $this->email = $this->contactPrefix . 'hello@example.com';

        // protected property — array passed to the template
        $this->team = [
            ['name' => 'Alice', 'role' => 'Design'],
            ['name' => 'Bob',   'role' => 'Engineering'],
        ];

        return parent::render();               // required — triggers template rendering
    }
}
```

### 2. Create the Template

Templates are plain PHP files. The template name is derived from the class name:
`AboutPresenter` → `templates/about.php`

Inside the template, `$this` refers to the presenter instance. Only `public` and `protected`
properties are accessible — `private` properties are not visible in the template.

```php
// src/Website/Presenter/templates/about.php
<?php $parent('base'); ?>

<main>
    <div class="container">
        <h1>About</h1>

        <?php if (!empty($this->team)): ?>
            <ul class="team">
                <?php foreach ($this->team as $member): ?>
                    <li>
                        <strong><?= htmlspecialchars($member['name']) ?></strong>
                        — <?= htmlspecialchars($member['role']) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <p>
            Contact us: <a href="<?= htmlspecialchars($this->email) ?>">hello@example.com</a>
        </p>

        <?php if ($this->section !== ''): ?>
            <p>Section: <?= htmlspecialchars($this->section) ?></p>
        <?php endif ?>

        <?php
            // $this->contactPrefix is private — accessing it here would throw an error.
            // Private properties belong to the presenter only.
        ?>
    </div>
</main>
```

`$parent('base')` wraps this template inside `base.php`. The child output is injected via `$children()`.

### 3. Register the Route

```php
// src/Website/Router/WebsiteRules.php — inside getRules()

// InputRule: URL → Presenter
$rules[] = new Router\InputRule('/^\/about\/?$/', function ($match) use ($presenterConstructor) {
    return new ActionHandler($presenterConstructor(AboutPresenter::class), []);
});

// OutputRule: Presenter → URL (reverse routing)
$rules[] = new Router\OutputRule(AboutPresenter::class, '/about/');
```

Both rules are required — InputRule for dispatch, OutputRule for `$this->link()`.

---

## Route Parameters

For dynamic segments, capture groups from the regex are passed as args:

```php
// InputRule with slug capture
$rules[] = new Router\InputRule('/^\/([a-z0-9_\-]+)\/?$/', function ($match) use ($presenterConstructor) {
    return new ActionHandler($presenterConstructor(PostPresenter::class), ['slug' => $match[1]]);
});
```

The arg key (`slug`) must match a **public** property on the presenter — Control injects it via `$presenter->slug = $value`.

```php
final class PostPresenter extends BasePresenter
{
    public string $slug = '';    // public = injected by Control from route args

    public function render(): ?string
    {
        // $this->slug is already set here
    }
}
```

### Property Visibility Rules

| Visibility  | Purpose | Accessible in template |
|-------------|---------|------------------------|
| `public`    | Route argument — injected by Control before `render()` | ✓ yes |
| `protected` | Internal data prepared in `render()`, shared with the template | ✓ yes |
| `private`   | Purely internal presenter logic — helper values, intermediates | ✗ no |

---

## Query Parameters

Read query params via the request URL — never directly on the request object:

```php
// In WebsiteRules InputRule closure:
$page = $request->getUrl()->getParam('page') ?? 1;
return new ActionHandler($presenterConstructor(PostsPresenter::class), ['page' => $page]);
```

---

## ApplicationContainer

The container is a lazy service locator. Add new services as nullable properties with a getter:

```php
private ?MyService $myService = null;

public function getMyService(): MyService
{
    if ($this->myService === null) {
        $config = $this->getEnv('my_service');
        $this->myService = new MyService($config['option']);
    }
    return $this->myService;
}
```

Access from any presenter via `$this->getContainer()->getMyService()`.

---

## PageContext

`BasePresenter` provides `$this->context` (a `PageContext` instance) for common head/body metadata:

```php
$this->context->title       = 'Page Title';
$this->context->description = 'Meta description.';
$this->context->lang        = 'en';
$this->context->canonical   = $this->link($this);   // always via router
$this->context->keywords    = 'foo, bar';

// Add arbitrary <head> elements:
$this->context->addHeaderElement(['meta', ['property' => 'og:image', 'content' => $url]]);
$this->context->addHeaderElement(['script', ['src' => '/js/app.js']]);
$this->context->css('/css/extra.css');
```

In `base.php`:

```php
<?= $this->context->createHeaderElements() ?>   // outputs all registered <head> elements
<?= $this->context->getBodyClassAttr() ?>        // outputs class="..." on <body>
<?= $this->context->createBodyElements() ?>      // outputs elements registered for <body>
```

---

## Canonical URLs — Always Use the Router

Never hardcode a presenter's own URL:

```php
// ✗ wrong — breaks silently if the route changes
$this->context->canonical = '/blog/';

// ✓ correct — always in sync with OutputRule
$this->context->canonical = $this->link($this);
```

For content-driven pages (where URL comes from data):

```php
// PostPresenter — URL comes from the post itself
$this->context->canonical = $this->post->getUrl();
```

---

## Generating Links to Other Presenters

```php
// Link to a presenter class (no instance needed)
$url = $this->link(HomePresenter::class);

// Link to a presenter with args
$url = $this->link(PostPresenter::class, ['slug' => 'my-post']);

// Link from the current presenter instance (includes current public property values)
$url = $this->link($this);
```

---

## Redirects and HTTP Utilities

```php
// Validate current URL and redirect to canonical if it doesn't match
$this->validatePresenterUrl();

// Mark response as no-cache
$this->nocache();

// Mark response as noindex (sends header + meta tags)
$this->noindex();

// Throw HTTP exceptions (handled by Control)
throw new NotFoundException('Post not found.');
```

---

## Template Inheritance

Templates use a simple parent/child system:

```php
// child template — first line declares parent
<?php $parent('base'); ?>
<main>...</main>
```

```php
// parent template (base.php) — renders child via $children()
<body>
    <?php include 'header.php'; ?>
    <?= $children() ?>
    <?php include 'footer.php'; ?>
</body>
```

Multiple levels are supported: `$parent('base')` → `$parent('layout')` → etc.

---

## Config

`config.php` returns a plain PHP array. Sections are accessed via `$container->getEnv('section')`:

```php
// config.php
return [
    'merkd' => [
        'db'           => __DIR__ . '/data/merkd.sqlite',
        'default_lang' => 'en',
    ],
];

// ApplicationContainer
$env = $this->getEnv('merkd');
$db  = $env['db'] ?? '';
```

Local overrides go in `config-dev.php` (loaded first, gitignored).

---

## Local Packages

Packages in `vendor-packages/` are loaded as Composer path repositories with symlinks:

```json
"repositories": {
    "dev-package": {
        "type": "path",
        "url": "vendor-packages/*",
        "options": { "symlink": true }
    }
}
```

Changes in `vendor-packages/` are immediately reflected — no `composer update` needed.
After adding a new package or class, run `composer dump-autoload`.
