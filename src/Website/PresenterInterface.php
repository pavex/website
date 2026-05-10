<?php

/**
 * Pavex Website v3 - PresenterInterface
 *
 * Contract for all presenter classes.
 * A presenter is responsible for preparing data and rendering its template.
 *
 * @author    Pavel Machacek <pavex@ines.cz>
 * @copyright 2009–2026 Pavel Macháček
 * @license   MIT
 * @package   Pavex\Website
 */

namespace Pavex\Website;


interface PresenterInterface
{
    public function render(): ?string;
}
