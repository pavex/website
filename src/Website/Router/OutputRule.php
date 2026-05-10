<?php

/**
 * Pavex Website v3 - OutputRule
 *
 * Generates a URL for a given presenter class name (reverse routing).
 * Supports a URL template string with {arg} placeholders or a Closure.
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website\Router;

use Closure;
use InvalidArgumentException;


class OutputRule implements OutputRuleInterface
{

    private string $presenter;
    private Closure|string $url;


    public function __construct(string $presenter, Closure|string $url)
    {
        $this->presenter = $presenter;
        $this->url = $url;
    }


    /**
     * Replaces {key} placeholders in the URL template with values from $args.
     *
     * @throws InvalidArgumentException when a referenced arg key is missing
     */
    protected function buildArgs(string $str, array $args): string
    {
        return preg_replace_callback('/\{([^\}]+)\}/', function (array $m) use ($args): string {
            $key = $m[1];

            if (!array_key_exists($key, $args)) {
                throw new InvalidArgumentException(sprintf('Undefined URL arg: %s', $key));
            }
            return (string) $args[$key];
        }, $str);
    }


    /**
     * Returns the URL for the given presenter class name, or null when this rule
     * does not handle that presenter.
     */
    public function getUrl(string $presenter, array $args = [], mixed $options = null): ?string
    {
        if ($this->presenter !== $presenter) {
            return null;
        }
        if ($this->url instanceof Closure) {
            return call_user_func($this->url, $args, $presenter, $this);
        }
        return $this->buildArgs($this->url, $args);
    }


}
