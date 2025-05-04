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
use Plib\CsrfProtector;
use Plib\FakeRequest;
use Plib\View;
use Realblog\Infra\DB;
use Realblog\Infra\FakeCsrfProtector;
use Realblog\Infra\FakeEditor;
use Realblog\Infra\Finder;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class MainAdminControllerTest extends TestCase
{
    /** @var CsrfProtector&Stub */
    private $csrfProtector;

    public function setUp(): void
    {
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("e3c1b42a6098b48a39f9f54ddb3388f7");
    }

    public function testSetsCookies()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_page=3",
            "admin" => true,
            "edit" => true,
        ]);
        $response = $sut($request);
        $this->assertEquals(
            ["realblog_page" => "3", "realblog_filter" => (string) Article::MASK_ALL],
            $response->cookies()
        );
    }

    public function testDefaultActionRendersOverview(): void
    {
        $sut = $this->sut(["finder" => ["articles" => $this->articles()]]);
        $request = new FakeRequest();
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testDefaultActionSetsFilterCookie()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_filter[]=0&realblog_filter[]=2",
        ]);
        $response = $sut($request);
        $this->assertEquals(["realblog_filter" => "5"], $response->cookies());
    }

    public function testDefaultActionGetsPageAndFilterFromCookie()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "admin" => true,
            "edit" => true,
            "cookie" => [
                "realblog_page" => "1",
                "realblog_filter" => "5",
            ],
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testCreateActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Create new article", $response->title());
    }

    public function testCreateActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "time" => 1675205155,
        ]);
        $sut($request);
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testCreateActionOutputsHjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->hjs());
    }

    public function testEditActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Edit article #1", $response->title());
    }

    public function testEditActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $sut($request);
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testEditActionOutputsHjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->hjs());
    }

    public function testEditActionRendersArticleWithAutoInputs(): void
    {
        $sut = $this->sut([
            "conf" => ["auto_publish" => "true", "auto_archive" => "true"],
            "finder" => ["article" => $this->firstArticle()]
        ]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testEditActionFailsOnMissingArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
        ]);
        $response = $sut($request);
        $this->assertStringContainsString("Article not found!", $response->output());
    }

    public function testDeleteActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Delete article #1", $response->title());
    }

    public function testDeleteActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $sut($request);
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testDeletectionOutputsHjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->hjs());
    }

    public function testDeleteActionFailsOnMissingArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
        ]);
        $response = $sut($request);
        $this->assertStringContainsString("Article not found!", $response->output());
    }

    public function testDoCreateActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $repsonse = $sut($request);
        $this->assertStringContainsString("You are not authorized for this action!", $repsonse->output());
    }

    public function testDoCreateActionRedirectsOnSuccess()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["insert" => 1]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoCreateActionReportsInvalidArticle(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->invalidPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals("Create new article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateActionFailureIsReported(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["insert" => 0]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=create",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals("Create new article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoEditActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoEditActionRedirectsOnSuccess()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["update" => 1]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoEditActionReportsInvalidArticle(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->invalidPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals("Edit article #-1", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoEditActionFailureIsReported(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["update" => 0]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals("Edit article #0", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

   public function testDoDeleteActionRedirectsOnSuccess()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["delete" => 1]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoDeleteActionFailureIsReported(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["delete" => 0]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete",
            "action" => "do_delete",
            "post" => $this->dummyPost(),
            "time" => 1675205155,
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete article #0", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteSelectedActionRendersConfirmation()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteSelectedActionReportsIfNothingIsSelected()
    {
        $sut = $this->sut();
        $request = new FakeRequest(["url" => "http://example.com/?&action=delete_selected"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionRendersConfirmation()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
        ]);
        $response = $sut($request);
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionReportsIfNothingIsSelected()
    {
        $sut = $this->sut();
        $request = new FakeRequest(["url" => "http://example.com/?&action=change_status"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoDeleteSelectedActionRedirectsOnSuccess()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["bulkDelete" => 2]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoDeleteSelectedActionReportsPartialSuccess()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["bulkDelete" => 1]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionReportsFailure()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["bulkDelete" => 0]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=delete_selected",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangeStatusActionIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoChangeStatusActionRedirectsOnSuccess()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["bulkUpdate" => 2]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text&realblog_page=1",
            $response->location()
        );
    }

    public function testDoChangeStatusActionReportsPartialSuccess()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["bulkUpdate" => 1]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangeStatusActionReportsFailure()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = $this->sut(["db" => ["bulkUpdate" => 0]]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&realblog_ids[]=17&realblog_ids[]=4&action=change_status",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    private function sut($options = [])
    {
        return new MainAdminController(
            "./plugins/realblog/",
            $this->conf($options["conf"] ?? []),
            $this->db($options["db"] ?? []),
            $this->finder($options["finder"] ?? []),
            $this->csrfProtector,
            $this->view(),
            $options["editor"] ?? new FakeEditor
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

    private function view()
    {
        return new View("./views/", XH_includeVar("./languages/en.php", 'plugin_tx')['realblog']);
    }

    private function conf($options = [])
    {
        $conf = XH_includeVar("./config/config.php", 'plugin_cf')['realblog'];
        return $options + $conf;
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
