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

class Pages
{
    public function hasPageWithUrl(string $url): bool
    {
        return in_array($url, $this->u(), true);
    }

    public function headingOf(int $num): string
    {
        return $this->h()[$num];
    }

    public function evaluateScripting(string $contents): string
    {
        return evaluate_scripting($contents);
    }

    /** @return list<string> */
    protected function h(): array
    {
        global $h;

        return $h;
    }

    /** @return list<string> */
    protected function u(): array
    {
        global $u;

        return $u;
    }
}
