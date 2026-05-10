<?php

/**
 * Pavex Website v3 - RouterInterface
 *
 * Combined contract for bidirectional routing.
 * Implementations handle both incoming URL matching and outgoing URL generation.
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website;

use Pavex\Website\Router\InputRuleInterface;
use Pavex\Website\Router\OutputRuleInterface;


interface RouterInterface extends InputRuleInterface, OutputRuleInterface
{
}
