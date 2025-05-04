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
use Realblog\Infra\DB;
use Realblog\Infra\FakePages;
use Realblog\Infra\Finder;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class BlogControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var DB&Stub */
    private $db;

    /** @var Finder&Stub */
    private $finder;

    /** @var View */
    private $view;

    /** @var FakePages */
    private $pages;

    public function setUp(): void
    {
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $this->db = $this->createStub(DB::class);
        $this->finder = $this->finder();
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["realblog"]);
        $this->pages = new FakePages();
    }

    private function sut(): BlogController
    {
        return new BlogController($this->conf, $this->db, $this->finder, $this->view, $this->pages);
    }

    public function testRendersArticleOverview(): void
    {
        $this->finder = $this->finder(["count" => 7, "articles" => $this->articles()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Blog",
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticle(): void
    {
        $this->conf["show_teaser"] = "true";
        $this->pages = new FakePages(["h" => ["", "Blog"]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Blog&realblog_id=3&realblog_search=word",
            "s" => 1,
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testSetsTitleAndDescription(): void
    {
        $this->pages = new FakePages(["h" => ["", "Blog"]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_id=3",
            "s" => 1,
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        $this->assertEquals("Blog – Title", $response->title());
        $this->assertEquals("Teaser", $response->description());
    }

    public function testRecordsPageView(): void
    {
        $this->db = $this->createMock(DB::class);
        $this->db->expects($this->once())->method("recordPageView")->with(3);
        $this->pages = new FakePages(["h" => ["", "Blog"]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_id=3",
            "s" => 1,
        ]);
        $this->sut()($request, "blog", false, "all");
    }

    public function testRendersEmptySearchResults(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_search=search",
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersOverviewWithComments()
    {
        $this->conf["comments_plugin"] = "Realblog\\Infra";
        $this->finder = $this->finder(["count" => 7, "articles" => $this->articles()]);
        $this->pages = new FakePages(["h" => ["", "Blog"]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Blog",
            "s" => 1,
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticleWithComments()
    {
        $this->conf["comments_plugin"] = "Realblog\\Infra";
        $this->pages = new FakePages(["h" => ["", "Blog"]]);
        $this->finder = $this->finder(["commentable" => true]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Blog&realblog_id=3&realblog_search=word",
            "s" => 1,
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticleWithHeadingAboveMeta(): void
    {
        $this->conf["heading_above_meta"] = "true";
        $this->conf["comments_plugin"] = "Realblog\\Infra";
        $this->finder = $this->finder(["count" => 7, "articles" => $this->articles()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Blog",
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticleWithCommentsAndAdminFeatures()
    {
        $this->conf["comments_plugin"] = "Realblog\\Infra";
        $this->conf["heading_above_meta"] = "true";
        $this->pages = new FakePages(["h" => ["", "Blog"]]);
        $this->finder = $this->finder(["commentable" => true]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Blog&realblog_id=3",
            "admin" => true,
            "s" => 1,
        ]);
        $response = $this->sut()($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersEmptyArchive(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Archive&realblog_year=2023",
        ]);
        $response = $this->sut()($request, "archive", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArchive(): void
    {
        $this->finder = $this->finder(["articles" => $this->archivedArticles()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Archive&realblog_year=2022",
            ]);
        $response = $this->sut()($request, "archive", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRedirectsToExplicitRealblogYear(): void
    {
        $this->finder = $this->finder(["articles" => $this->archivedArticles()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Archive",
        ]);
        $response = $this->sut()($request, "archive", true);
        $this->assertEquals("http://example.com/?Archive&realblog_year=2022", $response->location());
    }

    public function testRendersArchivedArticle(): void
    {
        $this->finder = $this->finder(["article" => $this->archivedArticle()]);
        $this->pages = new FakePages(["h" => ["irrelevant0", "irrelevant1", "Archive"]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Archive&realblog_id=3",
            "s" => 2,
        ]);
        $response = $this->sut()($request, "archive", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArchiveWithEmptySearchResults(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_search=search",
        ]);
        $response = $this->sut()($request, "archive", true, "all");
        Approvals::verifyHtml($response->output());
    }

    private function finder($options = [])
    {
        $finder = $this->createStub(Finder::class);
        $finder->method("countArticlesWithStatus")->willReturn($options["count"] ?? 0);
        $finder->method("findArticles")->willReturn($options["articles"] ?? []);
        $finder->method("findById")->willReturn($options["article"] ?? $this->article());
        $finder->method("findArchiveYears")->willReturn([2020, 2022]);
        $finder->method("findArchivedArticlesInPeriod")->willReturn($options["articles"] ?? []);
        $finder->method("findArchivedArticlesContaining")->willReturn([]);
        return $finder;
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
                ",test,",
                "Title $num",
                "Teaser $num",
                true,
                false,
                true
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
            ",test,",
            "Title",
            "Teaser",
            "Body",
            false,
            true
        );
    }

    private function archivedArticles(): array
    {
        $articles = [];
        foreach (range(2, 5) as $num) {
            $month = $num;
            $year = 2022;
            $articles[] = new Article(
                $num,
                gmmktime(12, 0, 0, $month, 14, $year),
                2,
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

    private function archivedArticle(): FullArticle
    {
        return new FullArticle(
            3,
            2,
            gmmktime(12, 0, 0, 6, 23, 2022),
            gmmktime(12, 0, 0, 6, 23, 2022),
            gmmktime(12, 0, 0, 6, 23, 2022),
            2,
            "",
            "Title",
            "Teaser",
            "Body",
            false,
            false
        );
    }
}
