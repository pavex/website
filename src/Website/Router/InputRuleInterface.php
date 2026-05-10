<?php

/**
 * Pavex Website v3 - InputRuleInterface
 *
 * Contract for incoming routing rules.
 * Implementations match a request against a pattern and return an ActionHandler,
 * or null when the rule does not apply.
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website\Router;

use Pavex\Http\Request;
use Pavex\Website\ActionHandler;


interface InputRuleInterface
{
    public function getActionHandler(Request $request): ?ActionHandler;
}
