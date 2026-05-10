<?php

/**
 * Pavex Website v3 - InvalidPresenterException
 *
 * Thrown when a constructor callable returns an object that does not
 * implement PresenterInterface.
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website\Exception;

use Exception;


class InvalidPresenterException extends Exception
{
}
