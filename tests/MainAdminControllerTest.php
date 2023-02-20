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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject;
use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Infra\View;
use Realblog\Value\FullArticle;
use ApprovalTests\Approvals;

use XH\CSRFProtection as CsrfProtector;

class MainAdminControllerTest extends TestCase
{
    /** @var MainAdminController */
    private $sut;

    /** @var Finder&MockObject */
    private $finder;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['realblog'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['realblog'];
        $db = $this->createStub(DB::class);
        $this->finder = $this->createStub(Finder::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", $lang);
        $editor = $this->createStub(Editor::class);
        $this->sut = new MainAdminController(
            "./",
            $conf,
            $lang,
            "/",
            "en",
            $db,
            $this->finder,
            $csrfProtector,
            $view,
            $editor,
            1675205155
        );
    }

    public function testDefaultActionRendersOverview(): void
    {
        $this->finder->method('findArticlesWithStatus')->willReturn([]);
        $response = ($this->sut)("");
        Approvals::verifyHtml($response->output());
    }

    public function testCreateActionRendersArticle(): void
    {
        $response = ($this->sut)("create");
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Create new article", $response->title());
    }

    public function testCreateActionOutputsHjs(): void
    {
        $response = ($this->sut)("create");
        Approvals::verifyHtml($response->hjs());
    }

    public function testCreateActionOutputsBjs(): void
    {
        $this->finder->method('findAllCategories')->willReturn(["cat1", "cat2"]);
        $response = ($this->sut)("create");
        Approvals::verifyHtml($response->bjs());
    }

    public function testEditActionRendersArticle(): void
    {
        $this->finder->method('findById')->willReturn($this->firstArticle());
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("edit");
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Edit article #1", $response->title());
    }

    public function testEditActionOutputsHjs(): void
    {
        $this->finder->method('findById')->willReturn($this->firstArticle());
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("edit");
        Approvals::verifyHtml($response->hjs());
    }

    public function testEditActionOutputsBjs(): void
    {
        $this->finder->method('findById')->willReturn($this->firstArticle());
        $this->finder->method('findAllCategories')->willReturn(["cat1", "cat2"]);
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("edit");
        Approvals::verifyHtml($response->bjs());
    }

    public function testEditActionFailsOnMissingArticle(): void
    {
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("edit");
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteActionRendersArticle(): void
    {
        $this->finder->method('findById')->willReturn($this->firstArticle());
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("delete");
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Delete article #1", $response->title());
    }

    public function testDeleteActionOutputsHjs(): void
    {
        $this->finder->method('findById')->willReturn($this->firstArticle());
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("delete");
        Approvals::verifyHtml($response->hjs());
    }

    public function testDeletectionOutputsBjs(): void
    {
        $this->finder->method('findById')->willReturn($this->firstArticle());
        $this->finder->method('findAllCategories')->willReturn(["cat1", "cat2"]);
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("delete");
        Approvals::verifyHtml($response->bjs());
    }

    public function testDeleteActionFailsOnMissingArticle(): void
    {
        $_GET = ['realblog_id' => "1"];
        $response = ($this->sut)("delete");
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateActionFailureIsReported(): void
    {
        $_POST = $this->dummyPost();
        $response = ($this->sut)("do_create");
        Approvals::verifyHtml($response->output());
    }

    public function testDoEditActionFailureIsReported(): void
    {
        $_POST = $this->dummyPost();
        $response = ($this->sut)("do_edit");
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteActionFailureIsReported(): void
    {
        $_POST = $this->dummyPost();
        $response = ($this->sut)("do_delete");
        Approvals::verifyHtml($response->output());
    }

    public function dummyPost(): array
    {
        return [
            'realblog_id' => "",
            'realblog_version' => "",
            'realblog_date' => "2023-02-01",
            'realblog_startdate' => "2023-02-01",
            'realblog_enddate' => "2024-02-01",
            'realblog_status' => "",
            'realblog_categories' => "",
            'realblog_title' => "",
            'realblog_headline' => "",
            'realblog_story' => "",
        ];
    }

    private function firstArticle(): FullArticle
    {
        return new FullArticle(
            1,
            1,
            1675205155,
            1675205155,
            0,
            "published",
            "cat1",
            "Welcome!",
            "Welcome to my wonderful new blog",
            "Some lengthy blog post.",
            true,
            false
        );
    }
}