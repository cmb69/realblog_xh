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
use PHPUnit\Framework\TestCase;
use Plib\View;
use Realblog\Infra\FakePages;
use Realblog\Infra\FakeRequest;
use Realblog\Infra\Finder;
use Realblog\Value\Article;

class LinkControllerTest extends TestCase
{
    public function testRendersLatestArticles(): void
    {
        $sut = new LinkController($this->conf(), new FakePages(["u" => ["Blog"]]), $this->finder(), $this->view());
        $response = $sut(new FakeRequest(), "Blog", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersNothingWhenPageDoesNotExist(): void
    {
        $sut = new LinkController($this->conf(), new FakePages(), $this->finder(), $this->view());
        $response = $sut(new FakeRequest(), "Blog", true);
        $this->assertEquals("", $response->output());
    }

    private function finder()
    {
        $finder = $this->createStub(Finder::class);
        $finder->method("findArticles")->willReturn([$this->article()]);
        return $finder;
    }

    private function view()
    {
        return new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["realblog"]);
    }

    private function conf()
    {
        return XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
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
