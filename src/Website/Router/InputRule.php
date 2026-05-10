<?php

/**
 * Pavex Website v3 - InputRule
 *
 * Matches an incoming request against a regex pattern applied to the full URL
 * (scheme + authority + path). On match, invokes a Closure or instantiates
 * a presenter class and returns an ActionHandler.
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website\Router;

use Pavex\Http\Request;
use Pavex\Website\ActionHandler;
use Closure;


class InputRule implements InputRuleInterface
{

    private string $pattern;
    private Closure|string $constructor;


    public function __construct(string $pattern, Closure|string $constructor)
    {
        $this->pattern = $pattern;
        $this->constructor = $constructor;
    }


    /**
     * Matches the full request URL against the pattern.
     * When matched with a Closure, calls it with ($match, $request, $this).
     * When matched with a class name string, creates an ActionHandler with URL query params as args.
     */
    public function getActionHandler(Request $request): ?ActionHandler
    {
        $relative_url = $request->getUrl()->path;

        if (preg_match($this->pattern, $relative_url, $match)) {
            if ($this->constructor instanceof Closure) {
                return call_user_func($this->constructor, $match, $request, $this);
            }
            return ActionHandler::create($this->constructor, $request->getUrl()->getParams());
        }
        return null;
    }


}
