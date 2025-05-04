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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Plib\FakeRequest;
use Plib\View;
use Realblog\Infra\FakePages;
use Realblog\Infra\Finder;
use Realblog\Value\Article;

class LinkControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var FakePages */
    private $pages;

    /** @var Finder&Stub */
    private $finder;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $this->pages = new FakePages();
        $this->finder = $this->createStub(Finder::class);
        $this->finder->method("findArticles")->willReturn([$this->article()]);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["realblog"]);
    }

    private function sut(): LinkController
    {
        return new LinkController($this->conf, $this->pages, $this->finder, $this->view);
    }

    public function testRendersLatestArticles(): void
    {
        $this->pages = new FakePages(["u" => ["Blog"]]);
        $response = $this->sut()(new FakeRequest(), "Blog", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersNothingWhenPageDoesNotExist(): void
    {
        $response = $this->sut()(new FakeRequest(), "Blog", true);
        $this->assertEquals("", $response->output());
    }

    private function article()
    {
        return new Article(
            1,
            strtotime("2023-02-23"),
            1,
            ",,",
            "My fine Post",
            "Read it",
            true,
            true,
            false
        );
    }
}
