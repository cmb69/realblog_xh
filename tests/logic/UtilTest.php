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

namespace Realblog;

use PHPUnit\Framework\TestCase;
use Realblog\Logic\Util;
use Realblog\Value\FullArticle;

class UtilTest extends TestCase
{
    /** @dataProvider dataForShortenText */
    public function testShortenText($text, $expected): void
    {
        $this->assertEquals($expected, Util::shortenText($text));
    }

    public function dataForShortenText(): array
    {
        return [
            "short" => ["short", "short"],
            "many" => [str_repeat("abcdefghijklmn ", 15), str_repeat("abcdefghijklmn ", 10) . "…"],
            "long" => [
                str_repeat("abcdefghijklmno", 15) . "p",
                str_repeat("abcdefghijklmno", 10) . "…",
            ],
        ];
    }

    /** @dataProvider dataForGatherPages */
    public function testGatherPages(int $page, int $count, int $radius, array $expected)
    {
        $actual = Util::gatherPages($page, $count, $radius);
        $this->assertEquals($expected, $actual);
    }

    public function dataForGatherPages(): array
    {
        return [
            "start" => [1, 9, 3, [1, 2, 3, 4, null, 9]],
            "middle" => [5, 9, 2, [1, null, 3, 4, 5, 6, 7, null, 9]],
            "end" => [9, 9, 3, [1, null, 6, 7, 8, 9]],
        ];
    }

    /** @dataProvider validateArticles */
    public function testValidateArticle(FullArticle $article, array $expected): void
    {
        $errors = Util::validateArticle($article);
        $this->assertEquals($expected, $errors);
    }

    public function validateArticles(): array
    {
        return [
            [new FullArticle(-1, -1, 0, 0, 0, 3, "", "", "", "", true, false), [
                ["error_id"],
                ["error_version"],
                ["error_date"],
                ["error_publishing_date"],
                ["error_archiving_date"],
                ["error_status"],
                ["error_title"],
            ]],
        ];
    }
}
