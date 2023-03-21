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
use Realblog\Infra\FakeCsrfProtector;
use Realblog\Infra\FakeEditor;
use Realblog\Infra\FakeRequest;
use Realblog\Infra\Finder;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use Realblog\Value\Url;

class MainAdminControllerTest extends TestCase
{
    public function testSetsPageCookie()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "admin" => true,
            "edit" => true,
            "url" => Url::from("http://example.com/?&realblog_page=3"),
        ]);
        $response = $sut($request);
        $this->assertEquals(["realblog_page" => "3"], $response->cookies());
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
            "url" => Url::from("http://example.com/?&realblog_filter[]=on&realblog_filter[]=&realblog_filter[]=on"),
        ]);
        $response = $sut($request);
        $this->assertEquals(["realblog_filter" => "[true,false,true]"], $response->cookies());
    }

    public function testDefaultActionGetsPageAndFilterFromCookie()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "admin" => true,
            "edit" => true,
            "cookie" => [
                "realblog_page" => "1",
                "realblog_filter" => "[true,false,true]",
            ],
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testCreateActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "action" => "create",
            "server" => ["REQUEST_TIME" => 1675205155]
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
            "action" => "create",
            "server" => ["REQUEST_TIME" => 1675205155]
        ]);
        $sut($request);
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testCreateActionOutputsHjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "action" => "create",
            "server" => ["REQUEST_TIME" => 1675205155]
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->hjs());
    }

    public function testCreateActionOutputsBjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "action" => "create",
            "server" => ["REQUEST_TIME" => 1675205155]
        ]);
        $response = $sut($request);
        Approvals::verifyHtml($response->bjs());
    }

    public function testEditActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["action" => "edit"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Edit article #1", $response->title());
    }

    public function testEditActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest(["action" => "edit"]);
        $sut($request);
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testEditActionOutputsHjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["action" => "edit"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->hjs());
    }

    public function testEditActionOutputsBjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["action" => "edit"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->bjs());
    }

    public function testEditActionRendersArticleWithAutoInputs(): void
    {
        $sut = $this->sut([
            "conf" => ["auto_publish" => "true", "auto_archive" => "true"],
            "finder" => ["article" => $this->firstArticle()]
        ]);
        $request = new FakeRequest(["action" => "edit"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testEditActionFailsOnMissingArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest(["action" => "edit"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["action" => "delete"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Delete article #1", $response->title());
    }

    public function testDeleteActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest(["action" => "delete"]);
        $sut($request);
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testDeletectionOutputsHjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["action" => "delete"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->hjs());
    }

    public function testDeletectionOutputsBjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["action" => "delete"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->bjs());
    }

    public function testDeleteActionFailsOnMissingArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest(["action" => "delete"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateActionIsCsrfProtected()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["insert" => 0]]);
        $request = new FakeRequest([
            "action" => "do_create",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155]],
        );
        $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoCreateActionRedirectsOnSuccess()
    {
        $sut = $this->sut(["db" => ["insert" => 1]]);
        $request = new FakeRequest([
            "action" => "do_create",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoCreateActionReportsInvalidArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "action" => "do_create",
            "post" => $this->invalidPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals("Create new article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateActionFailureIsReported(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["insert" => 0]]);
        $request = new FakeRequest([
            "action" => "do_create",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals("Create new article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoEditActionIsCsrfProtected()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["update" => 0]]);
        $request = new FakeRequest([
            "action" => "do_edit",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoEditActionRedirectsOnSuccess()
    {
        $sut = $this->sut(["db" => ["update" => 1]]);
        $request = new FakeRequest([
            "action" => "do_edit",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoEditActionReportsInvalidArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "action" => "do_edit",
            "post" => $this->invalidPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals("Edit article #-1", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoEditActionFailureIsReported(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["update" => 0]]);
        $request = new FakeRequest([
            "action" => "do_edit",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals("Edit article #0", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteActionIsCsrfProtected()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["delete" => 0]]);
        $request = new FakeRequest([
            "action" => "do_delete",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
    }

   public function testDoDeleteActionRedirectsOnSuccess()
    {
        $sut = $this->sut(["db" => ["delete" => 1]]);
        $request = new FakeRequest([
            "action" => "do_delete",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoDeleteActionFailureIsReported(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["delete" => 0]]);
        $request = new FakeRequest([
            "action" => "do_delete",
            "post" => $this->dummyPost(),
            "server" => ["REQUEST_TIME" => 1675205155],
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete article #0", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteSelectedActionRendersConfirmation()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "action" => "delete_selected",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteSelectedActionReportsIfNothingIsSelected()
    {
        $sut = $this->sut();
        $request = new FakeRequest(["action" => "delete_selected"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionRendersConfirmation()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "action" => "change_status",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $response = $sut($request);
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionReportsIfNothingIsSelected()
    {
        $sut = $this->sut();
        $request = new FakeRequest(["action" => "change_status"]);
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionIsCsrfProtected()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["bulkDelete" => 0]]);
        $request = new FakeRequest([
            "action" => "do_delete_selected",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoDeleteSelectedActionRedirectsOnSuccess()
    {
        $sut = $this->sut(["db" => ["bulkDelete" => 2]]);
        $request = new FakeRequest([
            "action" => "do_delete_selected",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $response = $sut($request);
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoDeleteSelectedActionReportsPartialSuccess()
    {
        $sut = $this->sut(["db" => ["bulkDelete" => 1]]);
        $request = new FakeRequest([
            "action" => "do_delete_selected",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionReportsFailure()
    {
        $sut = $this->sut(["db" => ["bulkDelete" => 0]]);
        $request = new FakeRequest([
            "action" => "do_delete_selected",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $response = $sut($request);
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangeStatusActionIsCsrfProtected()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["bulkUpdate" => 0]]);
        $request = new FakeRequest([
            "action" => "do_change_status",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoChangeStatusActionRedirectsOnSuccess()
    {
        $sut = $this->sut(["db" => ["bulkUpdate" => 2]]);
        $request = new FakeRequest([
            "action" => "do_change_status",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $response = $sut($request);
        $this->assertEquals("http://example.com/?realblog&admin=plugin_main&action=plugin_text", $response->location());
    }

    public function testDoChangeStatusActionReportsPartialSuccess()
    {
        $sut = $this->sut(["db" => ["bulkUpdate" => 1]]);
        $request = new FakeRequest([
            "action" => "do_change_status",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
        ]);
        $response = $sut($request);
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangeStatusActionReportsFailure()
    {
        $sut = $this->sut(["db" => ["bulkUpdate" => 0]]);
        $request = new FakeRequest([
            "action" => "do_change_status",
            "url" => Url::from("http://example.com/?&realblog_ids[]=17&realblog_ids[]=4"),
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
            $options["csrfProtector"] ?? new FakeCsrfProtector,
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
