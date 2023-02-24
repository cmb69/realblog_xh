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
use Realblog\Infra\DB;
use Realblog\Infra\FakePages;
use Realblog\Infra\FakeRequest;
use Realblog\Infra\Finder;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class BlogControllerTest extends TestCase
{
    public function testRendersArticleOverview(): void
    {
        $finder = $this->finder(["count" => 7, "articles" => $this->articles()]);
        $sut = new BlogController($this->conf(), $this->db(), $finder, $this->view(), new FakePages());
        $request = new FakeRequest(["su" => "Blog"]);
        $response = $sut($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticle(): void
    {
        $conf = $this->conf(["show_teaser" => "true"]);
        $pages = new FakePages(["h" => ["", "Blog"]]);
        $sut = new BlogController($conf, $this->db(), $this->finder(), $this->view(), $pages);
        $request = new FakeRequest([
            "s" => 1,
            "su" => "Blog",
            "get" => ["realblog_id" => "3", "realblog_search" => "word"]
        ]);
        $response = $sut($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testSetsTitleAndDescription(): void
    {
        $pages = new FakePages(["h" => ["", "Blog"]]);
        $sut = new BlogController($this->conf(), $this->db(), $this->finder(), $this->view(), $pages);
        $request = new FakeRequest(["s" => 1, "get" => ["realblog_id" => "3"]]);
        $response = $sut($request, "blog", true, "all");
        $this->assertEquals("Blog – Title", $response->title());
        $this->assertEquals("Teaser", $response->description());
    }

    public function testRecordsPageView(): void
    {
        $db = $this->createMock(DB::class);
        $db->expects($this->once())->method("recordPageView")->with(3);
        $pages = new FakePages(["h" => ["", "Blog"]]);
        $sut = new BlogController($this->conf(), $db, $this->finder(), $this->view(), $pages);
        $request = new FakeRequest(["s" => 1, "get" => ["realblog_id" => "3"]]);
        $sut($request, "blog", false, "all");
    }

    public function testRendersEmptySearchResults(): void
    {
        $sut = new BlogController($this->conf(), $this->db(), $this->finder(), $this->view(), new FakePages());
        $request = new FakeRequest(["get" => ["realblog_search" => "search"]]);
        $response = $sut($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testSetsCookieInEditMode()
    {
        $sut = new BlogController($this->conf(), $this->db(), $this->finder(), $this->view(), new FakePages());
        $request = new FakeRequest(["get" => ["realblog_page" => "3"], "admin" => true, "edit" => "true"]);
        $response = $sut($request, "blog", false, "all");
        $this->assertEquals(["realblog_page" => "3"], $response->cookies());
    }

    public function testRendersOverviewWithComments()
    {
        $conf = $this->conf(["comments_plugin" => "Realblog\\Infra"]);
        $finder = $this->finder(["count" => 7, "articles" => $this->articles()]);
        $pages = new FakePages(["h" => ["", "Blog"]]);
        $sut = new BlogController($conf, $this->db(), $finder, $this->view(), $pages);
        $request = new FakeRequest([
            "s" => 1,
            "su" => "Blog",
        ]);
        $response = $sut($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticleWithComments()
    {
        $conf = $this->conf(["comments_plugin" => "Realblog\\Infra"]);
        $pages = new FakePages(["h" => ["", "Blog"]]);
        $finder = $this->finder(["commentable" => true]);
        $sut = new BlogController($conf, $this->db(), $finder, $this->view(), $pages);
        $request = new FakeRequest([
            "s" => 1,
            "su" => "Blog",
            "get" => ["realblog_id" => "3", "realblog_search" => "word"]
        ]);
        $response = $sut($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticleWithHeadingAboveMeta(): void
    {
        $conf = $this->conf(["heading_above_meta" => "true", "comments_plugin" => "Realblog\\Infra"]);
        $finder = $this->finder(["count" => 7, "articles" => $this->articles()]);
        $sut = new BlogController($conf, $this->db(), $finder, $this->view(), new FakePages());
        $request = new FakeRequest(["su" => "Blog"]);
        $response = $sut($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArticleWithCommentsAndAdminFeatures()
    {
        $conf = $this->conf(["comments_plugin" => "Realblog\\Infra", "heading_above_meta" => "true"]);
        $pages = new FakePages(["h" => ["", "Blog"]]);
        $finder = $this->finder(["commentable" => true]);
        $sut = new BlogController($conf, $this->db(), $finder, $this->view(), $pages);
        $request = new FakeRequest([
            "admin" => true,
            "s" => 1,
            "su" => "Blog",
            "get" => ["realblog_id" => "3"]
        ]);
        $response = $sut($request, "blog", true, "all");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersEmptyArchive(): void
    {
        $sut = new BlogController($this->conf(), $this->db(), $this->finder(), $this->view(), new FakePages());
        $request = new FakeRequest(["su" => "Archive"]);
        $response = $sut($request, "archive", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArchive(): void
    {
        $finder = $this->finder(["articles" => $this->archivedArticles()]);
        $sut = new BlogController($this->conf(), $this->db(), $finder, $this->view(), new FakePages());
        $request = new FakeRequest(["su" => "Archive"]);
        $response = $sut($request, "archive", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArchivedArticle(): void
    {
        $finder = $this->finder(["article" => $this->archivedArticle()]);
        $pages = new FakePages(["h" => ["irrelevant0", "irrelevant1", "Archive"]]);
        $sut = new BlogController($this->conf(), $this->db(), $finder, $this->view(), $pages);
        $request = new FakeRequest(["s" => 2, "su" => "Archive", "get" => ["realblog_id" => "3"]]);
        $response = $sut($request, "archive", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersArchiveWithEmptySearchResults(): void
    {
        $sut = new BlogController($this->conf(), $this->db(), $this->finder(), $this->view(), new FakePages());
        $request = new FakeRequest(["get" => ["realblog_search" => "search"]]);
        $response = $sut($request, "archive", true, "all");
        Approvals::verifyHtml($response->output());
    }

    private function db()
    {
        return $this->createStub(DB::class);
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

    private function view()
    {
        return new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["realblog"]);
    }

    private function conf($options = [])
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        return $options + $conf;
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
        foreach (range(1, 7) as $num) {
            $month = (3 * $num) % 12 + 1;
            $year = intdiv(3 * $num, 12) + 2020;
            if ($year === 2021) {
                $year++;
            }
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
