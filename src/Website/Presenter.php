<?php

/**
 * Pavex Website v3 - Presenter
 *
 * Base class for all page presenters.
 * Guards against assignment to undeclared public properties.
 * Provides getArgs() to collect current public non-static property values for reverse routing.
 *
 * @author    Pavel Machacek <pavex@ines.cz>
 * @copyright 2009–2026 Pavel Macháček
 * @license   MIT
 * @package   Pavex\Website
 */

namespace Pavex\Website;

use Pavex\Website\PresenterInterface;
use ReflectionClass;
use ReflectionProperty;
use InvalidArgumentException;


abstract class Presenter extends Renderer implements PresenterInterface
{


    /**
     * Prevents assignment to properties not declared on the concrete presenter.
     * Ensures that only explicitly defined public properties receive route arguments.
     */
    public function __set(string $name, mixed $value): void
    {
        if (!property_exists($this, $name)) {
            throw new InvalidArgumentException(sprintf('Property %s::$%s is not declared.', static::class, $name));
        }
        $this->{$name} = $value;
    }


    /**
     * Returns all public non-static property values as an associative array.
     * Used by Router::getPresenterLink() for reverse URL generation.
     * Static properties (e.g. $templatePath, $templateExt from Renderer) are excluded.
     */
    public function getArgs(): array
    {
        $args = [];
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $args[$property->name] = $property->getValue($this);
        }
        return $args;
    }


}
