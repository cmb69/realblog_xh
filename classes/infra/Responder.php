<?php

/**
 * Copyright 2023 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Realblog\Infra;

use Realblog\Value\Response;

/** @codeCoverageIgnore */
class Responder
{
    /** @return string|never */
    public static function respond(Response $response)
    {
        global $title, $description, $hjs, $bjs;

        if ($response->location() !== null) {
            header("Location: " . $response->location());
            exit;
        }
        if ($response->contentType() !== null) {
            header("Content-Type: " . $response->contentType());
            echo $response->output();
            exit;
        }
        if ($response->title() !== null) {
            $title = $response->title();
        }
        if ($response->description() !== null) {
            $description = $response->description();
        }
        if ($response->hjs() !== null) {
            $hjs .= $response->hjs();
        }
        if ($response->bjs() !== null) {
            $bjs .= $response->bjs();
        }
        foreach ($response->cookies() as $name => $value) {
            setcookie($name, $value, 0, CMSIMPLE_ROOT);
        }
        return $response->output();
    }
}
