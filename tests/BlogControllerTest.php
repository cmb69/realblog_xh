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

use ApprovalTests\Approvals;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Realblog\Infra\DB;
use Realblog\Infra\Finder;
use Realblog\Infra\ScriptEvaluator;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class BlogControllerTest extends TestCase
{
    /** @var BlogController */
    private $sut;
    
    /** @var DB&MockObject */
    private $db;

    /** @var Finder&MockObject */
    private $finder;

    /** @var ScriptEvaluator&MockObject */
    private $scriptEvaluator;

    public function setUp(): void
    {
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["realblog"];
        $this->db = $this->createStub(DB::class);
        $this->finder = $this->createStub(Finder::class);
        $this->scriptEvaluator = $this->createStub(ScriptEvaluator::class);
        $this->scriptEvaluator->method("evaluate")->willReturnArgument(0);
        $this->sut = new BlogController(
            XH_includeVar("./config/config.php", "plugin_cf")["realblog"],
            $text,
            $this->db,
            $this->finder,
            new View("./views/", $text),
            $this->scriptEvaluator
        );
    }

    public function testRendersArticleOverview(): void
    {
        global $sn, $su;

        $sn = "/";
        $su = "Blog";
        $this->finder->method("findArticles")->willReturn($this->articles());
        $response = ($this->sut)(true, "all");
        Approvals::verifyHtml($response);
    }

    public function testRendersArticle(): void
    {
        global $sn, $su, $s, $h;

        $_GET = ["realblog_id" => "3"];
        $sn = "/";
        $su = "Blog";
        $s = 1;
        $h = [1 => "Blog"];
        $this->finder->method("findById")->willReturn($this->article());
        $response = ($this->sut)(true, "all");
        Approvals::verifyHtml($response);
    }

    private function articles(): array
    {
        $articles = [];
        foreach (range(1, 7) as $num) {
            $month = (3 * $num) % 12 + 1;
            $year = intdiv(3 * $num, 12) + 2021;
            $articles[] = new Article(
                $num,
                gmmktime(12, 0, 0, $month, 14, $year),
                1,
                "",
                "Title $num",
                "Teaser $num",
                false,
                false,
                false
            );
        }
        return $articles;
    }

    private function article(): FullArticle
    {
        return new FullArticle(
            3,
            1,
            gmmktime(12, 0, 0, 6, 23, 2023),
            gmmktime(12, 0, 0, 6, 23, 2023),
            gmmktime(12, 0, 0, 6, 23, 2023),
            1,
            "",
            "Title",
            "Teaser",
            "Body",
            false,
            false
        );
    }
}