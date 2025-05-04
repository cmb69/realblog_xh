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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Plib\CsrfProtector;
use Plib\FakeRequest;
use Plib\View;
use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class MainAdminControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var DB&MockObject */
    private $db;

    /** @var Finder&Stub */
    private $finder;

    /** @var CsrfProtector&Stub */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var Editor&MockObject */
    private $editor;

    public function setUp(): void
    {
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $this->db = $this->db();
        $this->finder = $this->finder();
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("e3c1b42a6098b48a39f9f54ddb3388f7");
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["realblog"]);
        $this->editor = $this->createMock(Editor::class);
    }

    private function sut()
    {
        return new MainAdminController(
            "./plugins/realblog/",
            $this->conf,
            $this->db,
            $this->finder,
            $this->csrfProtector,
            $this->view,
            $this->editor
        );
    }

    public function testDefaultActionRendersOverview(): void
    {
        $this->finder = $this->finder(["articles" => $this->articles()]);
        $request = new FakeRequest();
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testCreateActionRendersArticle(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Create new article", $response->title());
    }

    public function testCreateActionInitializesEditor(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $this->editor->expects($this->once())->method("init")
            ->with(["realblog_headline_field", "realblog_story_field"]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "time" => 1675205155,
        ]);
        $this->sut()($request);
    }

    public function testCreateActionOutputsHjs(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertSame("<meta name=\"realblog\" content='[\"cat1\",\"cat2\"]'>\n", $response->hjs());
    }

    public function testEditActionRendersArticle(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Edit article #1", $response->title());
    }

    public function testEditActionInitializesEditor(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $this->editor->expects($this->once())->method("init")
            ->with(["realblog_headline_field", "realblog_story_field"]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $this->sut()($request);
    }

    public function testEditActionOutputsHjs(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $this->sut()($request);
        $this->assertSame("<meta name=\"realblog\" content='[\"cat1\",\"cat2\"]'>\n", $response->hjs());
    }

    public function testEditActionRendersArticleWithAutoInputs(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $this->conf["auto_publish"] = "true";
        $this->conf["auto_archive"] = "true";
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testEditActionFailsOnMissingArticle(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Article not found!", $response->output());
    }

    public function testDeleteActionRendersArticle(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Delete article #1", $response->title());
    }

    public function testDeleteActionInitializesEditor(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $this->editor->expects($this->once())->method("init")
            ->with(["realblog_headline_field", "realblog_story_field"]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $this->sut()($request);
    }

    public function testDeletectionOutputsHjs(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $response = $this->sut()($request);
        $this->assertSame("<meta name=\"realblog\" content='[\"cat1\",\"cat2\"]'>\n", $response->hjs());
    }

    public function testDeleteActionFailsOnMissingArticle(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Article not found!", $response->output());
    }

    public function testDoCreateActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $repsonse = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $repsonse->output());
    }

    public function testDoCreateActionRedirectsOnSuccess()
    {
        $this->db = $this->db(["insert" => 1]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoCreateActionReportsInvalidArticle(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->invalidPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Create new article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateActionFailureIsReported(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $this->db = $this->db(["insert" => 0]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Create new article", $response->title());
        $this->assertStringContainsString("Article couldn't be added!", $response->output());
    }

    public function testDoEditActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoEditActionRedirectsOnSuccess()
    {
        $this->db = $this->db(["update" => 1]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoEditActionReportsInvalidArticle(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->invalidPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Edit article #-1", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoEditActionFailureIsReported(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $this->db = $this->db(["update" => 0]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Edit article #0", $response->title());
        $this->assertStringContainsString("Article couldn't be modified!", $response->output());
    }

    public function testDoDeleteActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

   public function testDoDeleteActionRedirectsOnSuccess()
    {
        $this->db = $this->db(["delete" => 1]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoDeleteActionFailureIsReported(): void
    {
        $this->finder = $this->finder(["article" => $this->firstArticle()]);
        $this->db = $this->db(["delete" => 0]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
            "action" => "do_delete",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Delete article #0", $response->title());
        $this->assertStringContainsString("Article couldn't be deleted!", $response->output());
    }

    public function testDeleteSelectedActionRendersConfirmation()
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteSelectedActionReportsIfNothingIsSelected()
    {
        $request = new FakeRequest(["url" => "http://example.com/?&action=delete_selected"]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionRendersConfirmation()
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionReportsIfNothingIsSelected()
    {
        $request = new FakeRequest(["url" => "http://example.com/?&action=change_status"]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoDeleteSelectedActionRedirectsOnSuccess()
    {
        $this->db = $this->db(["bulkDelete" => 2]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoDeleteSelectedActionReportsPartialSuccess()
    {
        $this->db = $this->db(["bulkDelete" => 1]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionReportsFailure()
    {
        $this->db = $this->db(["bulkDelete" => 0]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Delete selected articles", $response->title());
        $this->assertStringContainsString("No articles have been deleted!", $response->output());
    }

    public function testDoChangeStatusActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoChangeStatusActionRedirectsOnSuccess()
    {
        $this->db = $this->db(["bulkUpdate" => 2]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoChangeStatusActionReportsPartialSuccess()
    {
        $this->db = $this->db(["bulkUpdate" => 1]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangeStatusActionReportsFailure()
    {
        $this->db = $this->db(["bulkUpdate" => 0]);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Change article status", $response->title());
        $this->assertStringContainsString(
            "The status of the selected articles couldn't be changed!",
            $response->output()
        );
    }

    private function db($options = [])
    {
        $db = $this->createMock(DB::class);
        $db->expects(isset($options["bulkDelete"]) ? $this->once() : $this->never())
            ->method("deleteArticlesWithIds")->willReturn($options["bulkDelete"] ?? 0);
        $db->expects(isset($options["bulkUpdate"]) ? $this->once() : $this->never())
            ->method("updateStatusOfArticlesWithIds")->willReturn($options["bulkUpdate"] ?? 0);
        $db->expects(isset($options["insert"]) ? $this->once() : $this->never())
            ->method("insertArticle")->willReturn($options["insert"] ?? 0);
        $db->expects(isset($options["update"]) ? $this->once() : $this->never())
            ->method("updateArticle")->willReturn($options["update"] ?? 0);
        $db->expects(isset($options["delete"]) ? $this->once() : $this->never())
            ->method("deleteArticle")->willReturn($options["delete"] ?? 0);
        return $db;
    }

    private function finder($options = [])
    {
        $finder = $this->createStub(Finder::class);
        $finder->method("countArticlesWithStatus")->willReturn(count($options["articles"] ?? []));
        $finder->method('findArticlesWithStatus')->willReturn($options["articles"] ?? []);
        $finder->method('findById')->willReturn($options["article"] ?? null);
        $finder->method('findAllCategories')->willReturn(["cat1", "cat2"]);
        return $finder;
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
            'realblog_title' => "title",
            'realblog_headline' => "",
            'realblog_story' => "",
            "realblog_comments" => "",
            "realblog_rssfeed" => "",
            "realblog_do" => "",
        ];
    }

    public function invalidPost(): array
    {
        return [
            'realblog_id' => "-1",
            'realblog_version' => "-1",
            'realblog_date' => "",
            'realblog_startdate' => "",
            'realblog_enddate' => "",
            'realblog_status' => "3",
            'realblog_categories' => "",
            'realblog_title' => "",
            'realblog_headline' => "",
            'realblog_story' => "",
            "realblog_comments" => "",
            "realblog_rssfeed" => "",
            "realblog_do" => "",
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
            1,
            "cat1",
            "Welcome!",
            "Welcome to my wonderful new blog",
            "Some lengthy blog post.",
            true,
            false
        );
    }

    private function articles()
    {
        return [new Article(
            1,
            strtotime("2023-01-31T22:45:55+00:00"),
            Article::PUBLISHED,
            "",
            "Welcome!",
            "Welcome to my wonderful new blog",
            true,
            true,
            false
        )];
    }
}
