<?php

/**
 * Pavex Website v3 - CircularTemplateException
 *
 * Thrown when a template calls $parent() with a name already in the render chain.
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website\Exception;

use RuntimeException;


class CircularTemplateException extends RuntimeException
{
}
