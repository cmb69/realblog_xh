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
use Plib\FakeRequest;
use Plib\View;
use Realblog\Infra\FakePages;
use Realblog\Infra\Finder;
use Realblog\Value\MostPopularArticle;

class MostPopularControllerTest extends TestCase
{
    public function testRendersEmptyList(): void
    {
        $sut = new MostPopularController(
            $this->conf(),
            new FakePages(["u" => ["foo"]]),
            $this->finder([]),
            $this->view()
        );
        $response = $sut(new FakeRequest(), "foo");
        $this->assertStringContainsString("no entries available", $response->output());
    }

    public function testRendersMostPopularArticles(): void
    {
        $sut = new MostPopularController(
            $this->conf(),
            new FakePages(["u" => ["foo"]]),
            $this->finder($this->articles()),
            $this->view()
        );
        $response = $sut(new FakeRequest(), "foo");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersNothingIfPageDoesNotExist(): void
    {
        $sut = new MostPopularController(
            $this->conf(),
            new FakePages(),
            $this->finder($this->articles()),
            $this->view()
        );
        $response = $sut(new FakeRequest(), "bar");
        $this->assertSame("", $response->output());
    }

    private function finder($articles)
    {
        $finder = $this->createStub(Finder::class);
        $finder->method("findMostPopularArticles")->willReturn($articles);
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

    private function articles(): array
    {
        return [
            new MostPopularArticle(1, "Title 1", 300),
            new MostPopularArticle(2, "Title 2", 200),
            new MostPopularArticle(3, "Title 3", 100)
        ];
    }
}
