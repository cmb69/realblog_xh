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
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Value\Article;

class FeedControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var Finder&Stub */
    private $finder;

    /** @var Pages&Stub */
    private $pages;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["realblog"];
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $this->conf["rss_logo"] = "rss.png";
        $this->conf["rss_page"] = $text["rss_page"];
        $this->finder = $this->createStub(Finder::class);
        $this->pages = $this->createStub(Pages::class);
        $this->view = new View("./views/", $text);
    }

    private function sut(): FeedController
    {
        return new FeedController(
            "./userfiles/images/",
            $this->conf,
            $this->finder,
            $this->pages,
            $this->view
        );
    }

    public function testRendersFeedWithAnArticle(): void
    {
        $this->finder->method("findFeedableArticles")->willReturn([$this->article()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=realblog_feed",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testSetsAppropriateContentType()
    {
        $this->finder->method("findFeedableArticles")->willReturn([$this->article()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=realblog_feed",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("application/xml; charset=UTF-8", $response->contentType());
    }

    public function testRendersNothingWhenNotRequested(): void
    {
        $this->finder->method("findFeedableArticles")->willReturn([]);
        $request = new FakeRequest();
        $response = $this->sut()($request);
        $this->assertEquals("", $response->output());
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
