<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2023 Christoph M. Becker
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

use Plib\Request;
use Plib\Url;
use Plib\View;
use Realblog\Infra\CsrfProtector;
use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Logic\Util;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use Realblog\Value\Response;

class MainAdminController
{
    private const STATES = ['readyforpublishing', 'published', 'archived'];

    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $conf;

    /** @var DB */
    private $db;

    /** @var Finder */
    private $finder;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var Editor */
    private $editor;

    /** @param array<string,string> $conf */
    public function __construct(
        string $pluginFolder,
        array $conf,
        DB $db,
        Finder $finder,
        CsrfProtector $csrfProtector,
        View $view,
        Editor $editor
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->conf = $conf;
        $this->db = $db;
        $this->finder = $finder;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->editor = $editor;
    }

    public function __invoke(Request $request): Response
    {
        $response = $this->dispatch($request);
        if ($request->edit() && $request->get("realblog_page") !== null) {
            $page = max((int) $request->get("realblog_page"), 1);
            $response = $response->withCookie('realblog_page', (string) $page);
        }
        return $response;
    }

    private function dispatch(Request $request): Response
    {
        switch ($this->action($request)) {
            default:
                return $this->defaultAction($request);
            case "create":
                return $this->createAction($request);
            case "edit":
                return $this->editAction($request);
            case "delete":
                return $this->deleteAction($request);
            case "do_create":
                return $this->doCreateAction($request);
            case "do_edit":
                return $this->doEditAction($request);
            case "do_delete":
                return $this->doDeleteAction($request);
            case "delete_selected":
                return $this->deleteSelectedAction($request);
            case "change_status":
                return $this->changeStatusAction($request);
            case "do_delete_selected":
                return $this->doDeleteSelectedAction($request);
            case "do_change_status":
                return $this->doChangeStatusAction($request);
        }
    }

    private function action(Request $request): string
    {
        $action = $request->get("action");
        if (!is_string($action)) {
            return "";
        }
        if (!strncmp($action, "do_", strlen("do_"))) {
            return "";
        }
        if ($request->post("realblog_do") === null) {
            return $action;
        }
        return "do_$action";
    }

    private function defaultAction(Request $request): Response
    {
        $states = $this->stateFilter($request);
        $articleCount = $this->finder->countArticlesWithStatus($states);
        $limit = (int) $this->conf['admin_records_page'];
        [$offset, $pageCount] = Util::paginationOffset($articleCount, $limit, $this->realblogPage($request));
        $articles = $this->finder->findArticlesWithStatus($states, $limit, $offset);
        return Response::create($this->renderArticles($request, $articles, $pageCount))
            ->withCookie("realblog_filter", (string) $states);
    }

    /** @param list<Article> $articles */
    private function renderArticles(Request $request, array $articles, int $pageCount): string
    {
        $page = min($this->realblogPage($request), $pageCount);
        $states = $this->stateFilter($request);
        return $this->view->render("articles_form", [
            "imageFolder" => $this->pluginFolder . "images/",
            "page" => $page,
            "prevPage" => max($page - 1, 1),
            "nextPage" => min($page + 1, $pageCount),
            "lastPage" => $pageCount,
            "articles" => $this->articleRecords($request, $articles, $page),
            "states" => $this->stateTuples("checked", function (int $state) use ($states) {
                return (bool) ((1 << $state) & $states);
            }),
        ]);
    }

    /**
     * @param list<Article> $articles
     * @return list<array{id:int,date:string,status:int,categories:string,title:string,feedable:bool,commentable:bool,delete_url:string,edit_url:string}>
     */
    private function articleRecords(Request $request, array $articles, int $page)
    {
        $url = $request->url()->page("realblog")->with("admin", "plugin_main")
            ->with("realblog_page", (string) $page);
        return array_map(function (Article $article) use ($url) {
            $url = $url->with("realblog_id", (string) $article->id);
            return [
                "id" => $article->id,
                "date" => date($this->view->text("date_format"), $article->date),
                "status" => $article->status,
                "categories" => $article->categories,
                "title" => $article->title,
                "feedable" => $article->feedable,
                "commentable" => $article->commentable,
                "delete_url" => $url->with("action", "delete")->relative(),
                "edit_url" => $url->with("action", "edit")->relative(),
            ];
        }, $articles);
    }

    private function stateFilter(Request $request): int
    {
        $param = $request->getArray("realblog_filter");
        if (!is_array($param)) {
            $cookie = $request->cookie("realblog_filter");
            if ($cookie === null) {
                return Article::MASK_ALL;
            }
            return (int) $cookie;
        }
        $filters = 0;
        foreach ($param as $state) {
            if (!in_array($state, ["0", "1", "2"], true)) {
                continue;
            }
            $filters |= 1 << $state;
        }
        return $filters;
    }

    private function createAction(Request $request): Response
    {
        $timestamp = $request->time();
        $article = new FullArticle(0, 0, $timestamp, 2147483647, 2147483647, 0, '', '', '', '', false, false);
        return $this->showArticleEditor($article, "create");
    }

    private function editAction(Request $request): Response
    {
        $article = $this->finder->findById(max((int) ($request->get("realblog_id") ?? 0), 1));
        if (!$article) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        return $this->showArticleEditor($article, "edit");
    }

    private function deleteAction(Request $request): Response
    {
        $article = $this->finder->findById(max((int) ($request->get("realblog_id") ?? 0), 1));
        if (!$article) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        return $this->showArticleEditor($article, "delete");
    }

    private function doCreateAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = FullArticle::fromStrings(...$this->articlePost($request));
        $errors = Util::validateArticle($article);
        if ($errors) {
            return $this->showArticleEditor($article, "create", $errors);
        }
        $res = $this->db->insertArticle($article);
        if ($res !== 1) {
            return $this->showArticleEditor($article, "create", [["story_added_error"]]);
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    private function doEditAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = FullArticle::fromStrings(...$this->articlePost($request));
        $errors = Util::validateArticle($article);
        if ($errors) {
            return $this->showArticleEditor($article, "edit", $errors);
        }
        $res = $this->db->updateArticle($article);
        if ($res !== 1) {
            return $this->showArticleEditor($article, "edit", [["story_modified_error"]]);
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    private function doDeleteAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = FullArticle::fromStrings(...$this->articlePost($request));
        $res = $this->db->deleteArticle($article);
        if ($res !== 1) {
            return $this->showArticleEditor($article, "delete", [["story_deleted_error"]]);
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    /** @param list<array{string}> $errors */
    private function showArticleEditor(FullArticle $article, string $action, array $errors = []): Response
    {
        assert(in_array($action, ["create", "edit", "delete"], true));
        if ($action === "create") {
            $title = $this->view->text("tooltip_create");
        } elseif ($action === "edit") {
            $title = $this->view->text("title_edit", $article->id);
        } elseif ($action === "delete") {
            $title = $this->view->text("title_delete", $article->id);
        }
        $this->editor->init(['realblog_headline_field', 'realblog_story_field']);
        $json = json_encode(
            $this->finder->findAllCategories(),
            JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $hjs = "<meta name=\"realblog\" content='$json'>\n";
        return Response::create($this->renderArticleForm($article, $title, "btn_$action", $errors))
            ->withTitle($title)->withHjs($hjs);
    }

    /** @param list<array{string}> $errors */
    private function renderArticleForm(FullArticle $article, string $title, string $button, array $errors): string
    {
        return $this->view->render("article_form", [
            "id" => $article->id,
            "version" => $article->version,
            "status" => $article->status,
            "title" => $article->title,
            "teaser" => $article->teaser,
            "body" => $article->body,
            "feedable" => $article->feedable ? "checked" : "",
            "commentable" => $article->commentable ? "checked" : "",
            "page_title" => $title,
            "date" => (string) date("Y-m-d", $article->date),
            "publishing_date" => (string) date("Y-m-d", $article->publishingDate),
            "archiving_date" => (string) date("Y-m-d", $article->archivingDate),
            "csrfToken" => $this->csrfProtector->token(),
            "isAutoPublish" => (bool) $this->conf["auto_publish"],
            "isAutoArchive" => (bool) $this->conf["auto_archive"],
            "states" => $this->stateTuples("selected", function (int $state) use ($article) {
                return $state === $article->status;
            }),
            "categories" => trim($article->categories, ","),
            "button" => $button,
            "errors" => $errors,
            "script" => $this->pluginFolder . "realblog.js",
        ]);
    }

    /** @return array{string,string,string,string,string,string,string,string,string,string,string,string} */
    private function articlePost(Request $request): array
    {
        return [
            $request->post("realblog_id") ?? "",
            $request->post("realblog_version") ?? "",
            $request->post("realblog_date") ?? "",
            $request->post("realblog_startdate") ?? "",
            $request->post("realblog_enddate") ?? "",
            $request->post("realblog_status") ?? "",
            $request->post("realblog_categories") ?? "",
            $request->post("realblog_title") ?? "",
            $request->post("realblog_headline") ?? "",
            $request->post("realblog_story") ?? "",
            $request->post("realblog_rssfeed") ?? "",
            $request->post("realblog_comments") ?? "",
        ];
    }

    private function deleteSelectedAction(Request $request): Response
    {
        return Response::create($this->renderDeleteConfirmation($request))
            ->withTitle($this->view->text("tooltip_delete_selected"));
    }

    private function changeStatusAction(Request $request): Response
    {
        return Response::create($this->renderChangeStatusConfirmation($request))
            ->withTitle($this->view->text("tooltip_change_status"));
    }

    private function doDeleteSelectedAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $ids = $this->realblogIdsFromGet($request);
        $res = $this->db->deleteArticlesWithIds($ids);
        if ($res !== count($ids)) {
            $errors = $res > 0 ? [["deleteall_warning", $res, count($ids)]] : [["deleteall_error"]];
            return Response::create($this->renderDeleteConfirmation($request, $errors))
                ->withTitle($this->view->text("tooltip_delete_selected"));
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    private function doChangeStatusAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $ids = $this->realblogIdsFromGet($request);
        $status = min(max((int) ($request->post("realblog_status") ?? 0), 0), 2);
        $res = $this->db->updateStatusOfArticlesWithIds($ids, $status);
        if ($res !== count($ids)) {
            $errors = $res > 0 ? [["changestatus_warning", $res, count($ids)]] : [["changestatus_error"]];
            return Response::create($this->renderChangeStatusConfirmation($request, $errors))
                ->withTitle($this->view->text("tooltip_change_status"));
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderDeleteConfirmation(Request $request, array $errors = []): string
    {
        return $this->view->render("confirm_delete", [
            "ids" => $this->realblogIdsFromGet($request),
            "url" => $this->overviewUrl($request)->relative(),
            "csrfToken" => $this->csrfProtector->token(),
            "errors" => $errors,
        ]);
    }

    /** @param list<array{string}> $errors */
    private function renderChangeStatusConfirmation(Request $request, array $errors = []): string
    {
        return $this->view->render("confirm_change_status", [
            "ids" => $this->realblogIdsFromGet($request),
            "url" => $this->overviewUrl($request)->relative(),
            "csrfToken" => $this->csrfProtector->token(),
            "errors" => $errors,
            "states" => self::STATES,
        ]);
    }

    /** @return list<int> */
    private function realblogIdsFromGet(Request $request): array
    {
        $param = $request->getArray("realblog_ids");
        if ($param === null || !is_array($param)) {
            return [];
        }
        return array_map("intval", array_filter($param, function ($id) {
            return (int) $id >= 1;
        }));
    }

    /**
     * @param callable(int):bool $predicate
     * @return list<array{int,string,string}>
     */
    private function stateTuples(string $attribute, callable $predicate): array
    {
        return array_map(function (int $state, string $label) use ($attribute, $predicate) {
            return [$state, $label, $predicate($state) ? $attribute : ""];
        }, array_keys(self::STATES), array_values(self::STATES));
    }

    private function overviewUrl(Request $request): Url
    {
        return $request->url()->page("realblog")->with("admin", "plugin_main")->with("action", "plugin_text")
            ->with("realblog_page", (string) $this->realblogPage($request));
    }

    /** @return int */
    private function realblogPage(Request $request): int
    {
        $param = $request->get("realblog_page");
        if ($param !== null && is_string($param)) {
            return max((int) $param, 1);
        }
        if ($request->admin() && $request->edit()) {
            $cookie = $request->cookie("realblog_page");
            if ($cookie !== null) {
                return max((int) $cookie, 1);
            }
        }
        return 1;
    }
}
