# AGENT.md — AI Implementation Guide

This document provides strict instructions and patterns for AI agents working with the `pavex/website` framework. Adhere to these rules to ensure architectural integrity and consistency.

---

## Core Principles

1.  **Composition Over Complexity:** Keep presenters focused on data preparation and SEO. Move heavy logic to services in the `ApplicationContainer`.
2.  **Explicit Visibility:** Use property visibility to enforce the boundary between routing, logic, and rendering.
3.  **Bidirectional Routing:** Every page MUST have both an `InputRule` and an `OutputRule`.
4.  **No Magic:** Prefer explicit method calls (like `return parent::render()`) over hidden framework hooks.

---

## Implementation Rules

### 1. Presenter Lifecycle
- **NEVER** use `init()`. It does not exist.
- **ALWAYS** use `render(): ?string`.
- **ALWAYS** call `return parent::render()` at the end of `render()`.
- **Visibility:**
    - `public`: Only for properties that are injected via Router arguments.
    - `protected`: For all data intended for the template.
    - `private`: For internal helpers and temporary state.

### 2. Routing
- **InputRule Regex:** Use anchored regex (e.g., `/^\/path\/?$/`).
- **Capture Groups:** Map regex capture groups to `public` presenter properties in the `ActionHandler` args array.
- **OutputRule Pattern:** Use `{property}` placeholders that match `public` presenter properties.

### 3. Templates
- **Parent Declaration:** The first line MUST be `<?php $parent('layout_name'); ?>` if inheritance is used.
- **Escaping:** ALWAYS use `htmlspecialchars()` for any dynamic output unless it's explicitly trusted HTML (e.g., from a CMS builder).
- **Access:** Only access `$this->property` if it is `public` or `protected`.

---

## Step-by-Step: Adding a New Page

Follow this exact sequence when asked to add a new page:

1.  **Define the Presenter:** Create `src/Website/Presenter/NewPagePresenter.php`.
    - Extend `BasePresenter`.
    - Define `public` args.
    - Implement `render()` with SEO and data.
    - Call `parent::render()`.
2.  **Create the Template:** Create `src/Website/Presenter/templates/new-page.php`.
    - Declare `$parent`.
    - Build HTML using `$this->property`.
3.  **Register Rules:** Update `WebsiteRules::getRules()`.
    - Add `InputRule`.
    - Add `OutputRule`.
4.  **Validate:** Ensure `public` property names match exactly between the Presenter, the InputRule args, and the OutputRule pattern.

---

## Code Snippet Library

### Standard Presenter Pattern
```php
namespace App\Website\Presenter;

/**
 * @author pavex@ines.cz
 */
final class ProductPresenter extends BasePresenter
{
    public string $id = ''; // Route arg

    protected array $product = [];

    public function render(): ?string
    {
        $this->product = $this->getContainer()->getStore()->find($this->id);
        
        if (!$this->product) {
            throw new \Pavex\Http\Exception\NotFoundException();
        }

        $this->context->title = $this->product['title'];
        $this->context->canonical = $this->link($this);

        return parent::render();
    }
}
```

### Router Pattern (with param)
```php
// Input
$rules[] = new Router\InputRule('/^\/product\/([0-9]+)\/?$/', function ($match) use ($presenterConstructor) {
    return new ActionHandler($presenterConstructor(ProductPresenter::class), ['id' => $match[1]]);
});

// Output
$rules[] = new Router\OutputRule(ProductPresenter::class, '/product/{id}');
```

### Service Injection (ApplicationContainer)
```php
private ?MyService $myService = null;

public function getMyService(): MyService
{
    if ($this->myService === null) {
        $this->myService = new MyService($this->getEnv('my_service'));
    }
    return $this->myService;
}
```

---

## Common Pitfalls (Checklist for Code Review)

- [ ] Is there an `init()` method? (Delete it, move to `render()`).
- [ ] Is `return parent::render()` missing? (Add it).
- [ ] Is a route arg property `protected`? (Change to `public`).
- [ ] Is a template data property `public`? (Change to `protected`).
- [ ] Is a URL hardcoded in `canonical` or a link? (Use `$this->link()`).
- [ ] Is a template missing `htmlspecialchars()`? (Add it).

---
@author pavex@ines.cz
@author pavex@ines.cz
