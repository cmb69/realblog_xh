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
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Infra\Request;
use Realblog\Infra\Url;
use Realblog\Infra\View;

class FeedControllerTest extends TestCase
{
    public function testRendersFeedWithNoArticles(): void
    {
        global $su;

        $su = "";
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["realblog"];
        $conf["rss_page"] = $text["rss_page"];
        $finder = $this->createStub(Finder::class);
        $finder->method("findFeedableArticles")->willReturn([]);
        $pages = $this->createStub(Pages::class);
        $view = new View("./views/", $text);
        $sut = new FeedController($conf, $finder, $pages, $view);
        $request = $this->createStub(Request::class);
        $request->method("imageFolder")->willReturn("./userfiles/images/");
        $response = $sut($request);
        Approvals::verifyHtml($response);
    }

    public function testRendersFeedWithFeedLogo(): void
    {
        global $su;

        $su = "";
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $conf["rss_logo"] = "rss.png";
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["realblog"];
        $conf["rss_page"] = $text["rss_page"];
        $finder = $this->createStub(Finder::class);
        $finder->method("findFeedableArticles")->willReturn([]);
        $pages = $this->createStub(Pages::class);
        $view = new View("./views/", $text);
        $sut = new FeedController($conf, $finder, $pages, $view);
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url);
        $request->method("imageFolder")->willReturn("./userfiles/images/");
        $response = $sut($request);
        Approvals::verifyHtml($response);
    }
}
