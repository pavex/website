<?php

/**
 * Pavex Website v3 - ActionHandler
 *
 * Value object carrying a presenter constructor callable and its arguments.
 * Produced by InputRule on a successful URL match and consumed by Control
 * to instantiate and render the appropriate presenter.
 *
 * @author    Pavel Machacek <pavex@ines.cz>
 * @copyright 2009–2026 Pavel Macháček
 * @license   MIT
 * @package   Pavex\Website
 */

namespace Pavex\Website;


final class ActionHandler
{

    public readonly mixed $constructor;
    public readonly array $args;


    public function __construct(callable $constructor, array $args = [])
    {
        $this->constructor = $constructor;
        $this->args = $args;
    }


    public static function create(callable $constructor, array $args = []): self
    {
        return new self($constructor, $args);
    }


}
