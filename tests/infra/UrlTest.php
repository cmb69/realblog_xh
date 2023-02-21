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

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /** @dataProvider relativeUrlData */
    public function testRelativeUrls(string $base, string $path, string $expected): void
    {
        global $sn;

        $sn = $base;
        $url = new Url($path);
        $this->assertEquals($expected, $url->relative());
    }

    public function relativeUrlData(): array
    {
        return [
            ["/xh/", "", "/xh/"],
            ["/xh/de/", "", "/xh/de/"],
            ["/xh/", "./", "/xh/"],
            ["/xh/de/", "../", "/xh/"],
        ];
    }

    /** @dataProvider absoluteUrlData */
    public function testAbsoluteUrls(string $path, string $expected): void
    {
        $url = new Url($path);
        $this->assertEquals($expected, $url->absolute());
    }

    public function absoluteUrlData(): array
    {
        return [
            ["", "http://example.com/"],
            ["./", "http://example.com/"],
        ];
    }
}

