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

class MainAdminControllerTest extends TestCase
{
    public function testSetsPageCookie()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "admin" => true,
            "edit" => true,
            "get" => ["realblog_page" => "3"],
            "path" => ["folder" => ["plugins" => "./plugins/"]],
        ]);
        $response = $sut($request, "");
        $this->assertEquals(["realblog_page" => "3"], $response->cookies());
    }

    public function testDefaultActionRendersOverview(): void
    {
        $sut = $this->sut(["finder" => ["articles" => $this->articles()]]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $response = $sut($request, "");
        Approvals::verifyHtml($response->output());
    }

    public function testDefaultActionSetsFilterCookie()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "path" => ["folder" => ["plugins" => "./plugins/"]],
            "get" => ["realblog_filter" => ["on", "", "on"]],
        ]);
        $response = $sut($request, "");
        $this->assertEquals(["realblog_filter" => "[true,false,true]"], $response->cookies());
    }

    public function testDefaultActionGetsPageAndFilterFromCookie()
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "path" => ["folder" => ["plugins" => "./plugins/"]],
            "admin" => true,
            "edit" => true,
            "cookie" => [
                "realblog_page" => "1",
                "realblog_filter" => "[true,false,true]",
            ],
        ]);
        $response = $sut($request, "");
        Approvals::verifyHtml($response->output());
    }

    public function testCreateActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "path" => ["folder" => ["plugins" => "./plugins/"]],
            "server" => ["REQUEST_TIME" => 1675205155]
        ]);
        $response = $sut($request, "create");
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Create new article", $response->title());
    }

    public function testCreateActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest([
            "path" => ["folder" => ["plugins" => "./plugins/"]],
            "server" => ["REQUEST_TIME" => 1675205155]
        ]);
        $sut($request, "create");
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testCreateActionOutputsBjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest([
            "path" => ["folder" => ["plugins" => "./plugins/"]],
            "server" => ["REQUEST_TIME" => 1675205155]
        ]);
        $response = $sut($request, "create");
        Approvals::verifyHtml($response->bjs());
    }

    public function testEditActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $response = $sut($request, "edit");
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Edit article #1", $response->title());
    }

    public function testEditActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $sut($request, "edit");
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testEditActionOutputsBjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $response = $sut($request, "edit");
        Approvals::verifyHtml($response->bjs());
    }

    public function testEditActionRendersArticleWithAutoInputs(): void
    {
        $sut = $this->sut([
            "conf" => ["auto_publish" => "true", "auto_archive" => "true"],
            "finder" => ["article" => $this->firstArticle()]
        ]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $response = $sut($request, "edit");
        Approvals::verifyHtml($response->output());
    }

    public function testEditActionFailsOnMissingArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest();
        $response = $sut($request, "edit");
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteActionRendersArticle(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $response = $sut($request, "delete");
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Delete article #1", $response->title());
    }

    public function testDeleteActionInitializesEditor(): void
    {
        $editor = new FakeEditor;
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "editor" => $editor]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $sut($request, "delete");
        $this->assertEquals(["realblog_headline_field", "realblog_story_field"], $editor->classes());
    }

    public function testDeletectionOutputsBjs(): void
    {
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()]]);
        $request = new FakeRequest(["path" => ["folder" => ["plugins" => "./plugins/"]]]);
        $response = $sut($request, "delete");
        Approvals::verifyHtml($response->bjs());
    }

    public function testDeleteActionFailsOnMissingArticle(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest();
        $response = $sut($request, "delete");
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateActionIsCsrfProtected()
    {
        $_POST = $this->dummyPost();
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["insert" => 0]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $sut($request, "do_create");
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoCreateActionRedirectsOnSuccess()
    {
        $_POST = $this->dummyPost();
        $sut = $this->sut(["db" => ["insert" => 1]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $response = $sut($request, "do_create");
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoCreateActionFailureIsReported(): void
    {
        $_POST = $this->dummyPost();
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["insert" => 0]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $response = $sut($request, "do_create");
        $this->assertEquals("Create new article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoEditActionIsCsrfProtected()
    {
        $_POST = $this->dummyPost();
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["update" => 0]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $sut($request, "do_edit");
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoEditActionRedirectsOnSuccess()
    {
        $_POST = $this->dummyPost();
        $sut = $this->sut(["db" => ["update" => 1]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $response = $sut($request, "do_edit");
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoEditActionFailureIsReported(): void
    {
        $_POST = $this->dummyPost();
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["update" => 0]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $response = $sut($request, "do_edit");
        $this->assertEquals("Edit article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteActionIsCsrfProtected()
    {
        $_POST = $this->dummyPost();
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["delete" => 0]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $sut($request, "do_delete");
        $this->assertTrue($csrfProtector->hasChecked());
    }

   public function testDoDeleteActionRedirectsOnSuccess()
    {
        $_POST = $this->dummyPost();
        $sut = $this->sut(["db" => ["delete" => 1]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $response = $sut($request, "do_delete");
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoDeleteActionFailureIsReported(): void
    {
        $_POST = $this->dummyPost();
        $sut = $this->sut(["finder" => ["article" => $this->firstArticle()], "db" => ["delete" => 0]]);
        $request = new FakeRequest(["server" => ["REQUEST_TIME" => 1675205155]]);
        $response = $sut($request, "do_delete");
        $this->assertEquals("Delete article", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteSelectedActionRendersConfirmation()
    {
        $sut = $this->sut();
        $request = new FakeRequest(["get" => ["realblog_ids" => ["17", "4"]]]);
        $response = $sut($request, "delete_selected");
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteSelectedActionReportsIfNothingIsSelected()
    {
        $sut = $this->sut();
        $request = new FakeRequest();
        $response = $sut($request, "delete_selected");
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionRendersConfirmation()
    {
        $sut = $this->sut();
        $request = new FakeRequest(["get" => ["realblog_ids" => ["17", "4"]]]);
        $response = $sut($request, "change_status");
        Approvals::verifyHtml($response->output());
    }

    public function testChangeStatusActionReportsIfNothingIsSelected()
    {
        $sut = $this->sut();
        $request = new FakeRequest();
        $response = $sut($request, "change_status");
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionIsCsrfProtected()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["bulkDelete" => 0]]);
        $request = new FakeRequest();
        $sut($request, "do_delete_selected");
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoDeleteSelectedActionRedirectsOnSuccess()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $sut = $this->sut(["db" => ["bulkDelete" => 2]]);
        $request = new FakeRequest();
        $response = $sut($request, "do_delete_selected");
        $this->assertEquals(
            "http://example.com/?realblog&admin=plugin_main&action=plugin_text",
            $response->location()
        );
    }

    public function testDoDeleteSelectedActionReportsPartialSuccess()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $sut = $this->sut(["db" => ["bulkDelete" => 1]]);
        $request = new FakeRequest();
        $response = $sut($request, "do_delete_selected");
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteSelectedActionReportsFailure()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $sut = $this->sut(["db" => ["bulkDelete" => 0]]);
        $request = new FakeRequest();
        $response = $sut($request, "do_delete_selected");
        $this->assertEquals("Delete selected articles", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangeStatusActionIsCsrfProtected()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => ["bulkUpdate" => 0]]);
        $request = new FakeRequest();
        $sut($request, "do_change_status");
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testDoChangeStatusActionRedirectsOnSuccess()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $sut = $this->sut(["db" => ["bulkUpdate" => 2]]);
        $request = new FakeRequest();
        $response = $sut($request, "do_change_status");
        $this->assertEquals("http://example.com/?realblog&admin=plugin_main&action=plugin_text", $response->location());
    }

    public function testDoChangeStatusActionReportsPartialSuccess()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $sut = $this->sut(["db" => ["bulkUpdate" => 1]]);
        $request = new FakeRequest();
        $response = $sut($request, "do_change_status");
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangeStatusActionReportsFailure()
    {
        $_POST = ["realblog_ids" => ["17", "4"]];
        $sut = $this->sut(["db" => ["bulkUpdate" => 0]]);
        $request = new FakeRequest();
        $response = $sut($request, "do_change_status");
        $this->assertEquals("Change article status", $response->title());
        Approvals::verifyHtml($response->output());
    }

    private function sut($options = [])
    {
        return new MainAdminController(
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
