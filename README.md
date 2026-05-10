# Pavex Website

**The high-performance, SEO-centric PHP 8.0+ engine for developers who demand maximum control and zero bloat.**

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892bf.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## 🌟 Philosophy: Beyond "Just Another Framework"

Pavex Website isn't a general-purpose application framework. It is a specialized, opinionated **Website Engine**. 

In an era where web performance and Core Web Vitals directly impact business success, Pavex Website strips away the heavy abstractions of traditional MVC frameworks. It provides a surgical toolset for building content-driven sites that are lightning-fast, easy to maintain, and perfectly optimized for search engines from day one.

### The "Website-First" Approach
While other frameworks treat a "website" as a simplified "web app," Pavex Website acknowledges that websites have unique requirements:
- **Instant Response Times:** Minimal overhead between the request and the response.
- **Semantic Integrity:** Total control over every byte of HTML output.
- **Dynamic SEO:** First-class support for canonicals, meta tags, and structured data.
- **Bi-directional Clarity:** Routing that is as easy to read as it is to write.

---

## Why Choose this package.

- **Extreme Performance:** No heavy DI containers, no complex middleware stacks, no bloated ORM. Just raw PHP speed with a refined architectural structure.
- **SEO Mastery:** Integrated `PageContext` makes managing complex SEO requirements (canonicals, OpenGraph, lang attributes, breadcrumbs) a part of your natural workflow, not an afterthought.
- **Pure PHP Templates:** Forget learning yet another template syntax like Blade or Twig. Use pure PHP for logic and template inheritance that is both powerful and transparent.
- **Integrity by Design:** Strict property visibility rules (Public for route args, Protected for templates) prevent common state-leakage bugs and make your code self-documenting.
- **Smart Reverse Routing:** Change a URL pattern in one place, and every link across your entire site updates automatically. No more broken internal links.

---

## 🛠️ What Can You Build?

Pavex Website is the ideal foundation for:
- **Corporate Portals:** Fast, accessible, and professional brand presences.
- **Content Magazines & Blogs:** SEO-optimized platforms for publishers.
- **Marketing Landing Pages:** High-conversion pages with no technical debt.
- **Product Showcases:** Visually rich frontends backed by a clean data layer.
- **E-commerce Frontends:** Performance-critical storefronts for modern shops.

---

## 🏗️ Core Architecture

The framework operates on three primary components:

1.  **Router:** Maps URLs to Presenters (Input) and Presenters back to URLs (Output).
2.  **Presenter:** The "Controller". It prepares data, manages SEO metadata, and triggers rendering.
3.  **Control:** The orchestrator. It handles the request/response lifecycle and dependency injection.

---

## 🚦 Getting Started

### 1. Installation

```bash
composer require pavex/website
```

### 2. Recommended Directory Structure

```text
src/
  Website/
    Application.php           # App bootstrapper
    ApplicationContainer.php  # Service locator
    Presenter/
      BasePresenter.php       # Shared logic (SEO, common data)
      HomePresenter.php       # Your first page
      templates/
        base.php              # Main layout
        home.php              # Page template
    Router/
      WebsiteRules.php        # Route definitions
```

### 3. Creating a Presenter

The presenter lifecycle centers around the `render()` method.

```php
namespace App\Website\Presenter;

use Pavex\Website\Presenter;

final class HomePresenter extends BasePresenter
{
    // Public properties are automatically injected from route arguments
    public string $section = 'default';

    // Protected properties are available in the template via $this
    protected array $features = [];

    public function render(): ?string
    {
        // 1. Prepare SEO metadata
        $this->context->title = 'Welcome to My Website';
        $this->context->description = 'Building fast websites with Pavex.';
        $this->context->canonical = $this->link($this);

        // 2. Prepare data for template
        $this->features = ['Speed', 'Simplicity', 'SEO'];

        // 3. Trigger rendering (MANDATORY)
        return parent::render();
    }
}
```

### 4. Creating a Template

Templates are located in the `templates/` subdirectory relative to the Presenter.

```php
// templates/home.php
<?php $parent('base'); ?>

<main>
    <h1>Welcome!</h1>
    <p>Current section: <?= htmlspecialchars($this->section) ?></p>

    <ul>
        <?php foreach ($this->features as $feature): ?>
            <li><?= htmlspecialchars($feature) ?></li>
        <?php endforeach ?>
    </ul>
</main>
```

### 5. Registering Routes

Routes are defined as pairs of `InputRule` and `OutputRule`.

```php
// src/Website/Router/WebsiteRules.php
use Pavex\Website\Router;
use Pavex\Website\ActionHandler;

$rules = [];

// URL -> Presenter
$rules[] = new Router\InputRule('/^\/$/', function ($match) use ($presenterConstructor) {
    return new ActionHandler($presenterConstructor(HomePresenter::class), []);
});

// Presenter -> URL (for $this->link())
$rules[] = new Router\OutputRule(HomePresenter::class, '/');

return $rules;
```

---

## 📖 Key Concepts

### Property Visibility Rules

| Visibility | Purpose | In Template |
| :--- | :--- | :--- |
| `public` | **Route Arguments**. Injected by `Control` before `render()`. | ✅ Yes |
| `protected` | **Template Data**. Prepared inside `render()`. | ✅ Yes |
| `private` | **Internal Logic**. Not accessible in templates. | ❌ No |

### The `render()` Method

- **Always** use `render(): ?string`. There is no `init()` method.
- **Always** end with `return parent::render()`. Without this, your template will not render.

### Link Generation

Never hardcode URLs. Always use the Router to ensure links are always in sync with your rules.

```php
// Link to a class
$url = $this->link(AboutPresenter::class);

// Link with arguments
$url = $this->link(PostPresenter::class, ['slug' => 'hello-world']);

// Link to current page (canonical)
$url = $this->link($this);
```

### SEO & Metadata (`PageContext`)

The `BasePresenter` (if extended) provides a `$this->context` object to manage the `<head>` and `<body>` tags.

```php
$this->context->title = 'Page Title';
$this->context->addHeaderElement(['meta', ['name' => 'robots', 'content' => 'index,follow']]);
$this->context->css('/assets/css/main.css');
$this->context->addBodyClass('is-home');
```

---

## 📜 License

Pavex Website is open-source software licensed under the [MIT license](LICENSE).

---
@author pavex@ines.cz
