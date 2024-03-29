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

use Realblog\Infra\CsrfProtector;
use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Infra\Request;
use Realblog\Infra\View;
use Realblog\Logic\Util;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use Realblog\Value\Response;
use Realblog\Value\Url;

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
        if ($request->edit() && $request->url()->param("realblog_page") !== null) {
            $page = max($request->intFromGet("realblog_page"), 1);
            $response = $response->withCookie('realblog_page', (string) $page);
        }
        return $response;
    }

    private function dispatch(Request $request): Response
    {
        switch ($request->action()) {
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

    private function defaultAction(Request $request): Response
    {
        $states = $request->stateFilter();
        $articleCount = $this->finder->countArticlesWithStatus($states);
        $limit = (int) $this->conf['admin_records_page'];
        [$offset, $pageCount] = Util::paginationOffset($articleCount, $limit, $request->realblogPage());
        $articles = $this->finder->findArticlesWithStatus($states, $limit, $offset);
        return Response::create($this->renderArticles($request, $articles, $pageCount))
            ->withCookie("realblog_filter", (string) $states);
    }

    /** @param list<Article> $articles */
    private function renderArticles(Request $request, array $articles, int $pageCount): string
    {
        $page = min($request->realblogPage(), $pageCount);
        $states = $request->stateFilter();
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
        $url = $request->url()->withPage("realblog")->with("admin", "plugin_main")
            ->with("realblog_page", (string) $page);
        return array_map(function (Article $article) use ($url) {
            $url = $url->with("realblog_id", (string) $article->id);
            return [
                "id" => $article->id,
                "date" => $this->view->date($article->date),
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

    private function createAction(Request $request): Response
    {
        $timestamp = $request->time();
        $article = new FullArticle(0, 0, $timestamp, 2147483647, 2147483647, 0, '', '', '', '', false, false);
        return $this->showArticleEditor($article, "create");
    }

    private function editAction(Request $request): Response
    {
        $article = $this->finder->findById(max($request->intFromGet("realblog_id"), 1));
        if (!$article) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        return $this->showArticleEditor($article, "edit");
    }

    private function deleteAction(Request $request): Response
    {
        $article = $this->finder->findById(max($request->intFromGet("realblog_id"), 1));
        if (!$article) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        return $this->showArticleEditor($article, "delete");
    }

    private function doCreateAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = FullArticle::fromStrings(...$request->articlePost());
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
        $article = FullArticle::fromStrings(...$request->articlePost());
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
        $article = FullArticle::fromStrings(...$request->articlePost());
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
            $title = $this->view->text("title_edit", $this->view->esc($article->id));
        } elseif ($action === "delete") {
            $title = $this->view->text("title_delete", $this->view->esc($article->id));
        }
        $this->editor->init(['realblog_headline_field', 'realblog_story_field']);
        $hjs = $this->view->renderMeta("realblog", $this->finder->findAllCategories());
        $bjs = $this->view->renderScript($this->pluginFolder . "realblog.js");
        return Response::create($this->renderArticleForm($article, $title, "btn_$action", $errors))
            ->withTitle($title)->withHjs($hjs)->withBjs($bjs);
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
        ]);
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
        $ids = $request->realblogIdsFromGet();
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
        $ids = $request->realblogIdsFromGet();
        $res = $this->db->updateStatusOfArticlesWithIds($ids, $request->statusFromPost());
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
            "ids" => $request->realblogIdsFromGet(),
            "url" => $this->overviewUrl($request)->relative(),
            "csrfToken" => $this->csrfProtector->token(),
            "errors" => $errors,
        ]);
    }

    /** @param list<array{string}> $errors */
    private function renderChangeStatusConfirmation(Request $request, array $errors = []): string
    {
        return $this->view->render("confirm_change_status", [
            "ids" => $request->realblogIdsFromGet(),
            "url" => $this->overviewUrl($request)->relative(),
            "csrfToken" => $this->csrfProtector->token(),
            "errors" => $errors,
            "states" => self::STATES,
        ]);
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
        return $request->url()->withPage("realblog")->with("admin", "plugin_main")->with("action", "plugin_text")
            ->with("realblog_page", (string) $request->realblogPage());
    }
}
