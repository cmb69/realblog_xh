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
use Realblog\Infra\FakeRequest;
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Infra\View;
use Realblog\Value\Article;

class FeedControllerTest extends TestCase
{
    public function testRendersFeedWithAnArticle(): void
    {
        $sut = new FeedController(
            "./userfiles/images/",
            $this->conf(),
            $this->finder([$this->article()]),
            $this->pages(),
            $this->view()
        );
        $request = new FakeRequest([
            "server" => ["QUERY_STRING" => "&function=realblog_feed"],
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testSetsAppropriateContentType()
    {
        $sut = new FeedController(
            "./userfiles/images/",
            $this->conf(),
            $this->finder([$this->article()]),
            $this->pages(),
            $this->view()
        );
        $request = new FakeRequest([
            "server" => ["QUERY_STRING" => "&function=realblog_feed"],
        ]);
        $response = $sut($request);
        $this->assertEquals("application/xml; charset=UTF-8", $response->contentType());
    }

    public function testRendersNothingWhenNotRequested(): void
    {
        $sut = new FeedController(
            "./userfiles/images/",
            $this->conf(),
            $this->finder([]),
            $this->pages(),
            $this->view()
        );
        $request = new FakeRequest();
        $response = $sut($request);
        $this->assertEquals("", $response->output());
    }

    private function finder(array $articles): Finder
    {
        $finder = $this->createStub(Finder::class);
        $finder->method("findFeedableArticles")->willReturn($articles);
        return $finder;
    }

    private function pages(): Pages
    {
        return $this->createStub(Pages::class);
    }

    private function view(): View
    {
        return new View("./views/", $this->text());
    }

    private function conf(): array
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $conf["rss_logo"] = "rss.png";
        $conf["rss_page"] = $this->text()["rss_page"];
        return $conf;
    }

    private function text(): array
    {
        return XH_includeVar("./languages/en.php", "plugin_tx")["realblog"];
    }

    private function article(): Article
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
