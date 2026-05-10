<?php

/**
 * Pavex Website v3 - OutputRuleInterface
 *
 * Contract for outgoing routing rules (reverse routing).
 * Implementations generate a URL for a given presenter class name and arguments,
 * or return null when the rule does not handle that presenter.
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website\Router;


interface OutputRuleInterface
{
    public function getUrl(string $presenter, array $args = [], mixed $options = null): ?string;
}
