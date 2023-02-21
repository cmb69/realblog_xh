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

use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Realblog\Infra\Finder;
use Realblog\Infra\View;
use ApprovalTests\Approvals;
use Realblog\Value\MostPopularArticle;

class MostPopularControllerTest extends TestCase
{
    /** @var MostPopularController&MockObject */
    private $sut;

    /** @var Finder&MockObject */
    private $finder;

    public function setUp(): void
    {
        $conf = XH_includeVar("./config/config.php", 'plugin_cf')['realblog'];
        $text = XH_includeVar("./languages/en.php", 'plugin_tx')['realblog'];
        $this->finder = $this->createStub(Finder::class);
        $view = new View("./views/", $text);
        $this->sut = new MostPopularController($conf, ["foo"], $this->finder, $view);
    }

    public function testRendersEmptyList(): void
    {
        $this->finder->method("findMostPopularArticles")->willReturn([]);
        $response = ($this->sut)("foo");
        Approvals::verifyHtml($response);
    }

    public function testRendersMostPopularArticles(): void
    {
        $this->finder->method("findMostPopularArticles")->willReturn($this->articles());
        $response = ($this->sut)("foo");
        Approvals::verifyHtml($response);
    }

    public function testRendersNothingIfPageDoesNotExist(): void
    {
        $this->finder->method("findMostPopularArticles")->willReturn($this->articles());
        $response = ($this->sut)("bar");
        Approvals::verifyHtml($response);
    }

    private function articles(): array
    {
        return [
            new MostPopularArticle(1, "Title 1", 300),
            new MostPopularArticle(2, "Title 2", 200),
            new MostPopularArticle(3, "Title 3", 100)
        ];
    }
}
