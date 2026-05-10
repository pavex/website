<?php

/**
 * Pavex Website v3 - Component
 *
 * Base class for all renderable UI components.
 * Provides bulk property assignment via setProps() and string casting via __toString().
 *
 * @author    Pavel Machacek <pavex@ines.cz>
 * @copyright 2009–2026 Pavel Macháček
 * @license   MIT
 * @package   Pavex\Website
 */

namespace Pavex\Website;


abstract class Component
{

    public function setProps(array $props): void
    {
        foreach ($props as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }


    public function render(): ?string
    {
        return null;
    }


    final public function __toString(): string
    {
        return $this->render() ?: '';
    }


}
