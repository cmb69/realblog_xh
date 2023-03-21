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
use Realblog\Value\Html;
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
        $statuses = array_keys(array_filter($this->getFilterStatuses($request)));
        $total = $this->finder->countArticlesWithStatus($statuses);
        $limit = (int) $this->conf['admin_records_page'];
        $pageCount = (int) ceil($total / $limit);
        $page = min($request->realblogPage(), $pageCount);
        $offset = ($page - 1) * $limit;
        $articles = $this->finder->findArticlesWithStatus($statuses, $limit, $offset);
        $response = Response::create($this->renderArticles($request, $articles, $pageCount));
        $filters = $request->filtersFromGet();
        if ($filters !== null && $filters !== $request->filtersFromCookie()) {
            $response = $response->withCookie("realblog_filter", (string) json_encode($filters));
        }
        return $response;
    }

    /** @return list<bool> */
    private function getFilterStatuses(Request $request): array
    {
        return $request->filtersFromGet() ?? $request->filtersFromCookie() ?? [false, false, false];
    }

    /** @param list<Article> $articles */
    private function renderArticles(Request $request, array $articles, int $pageCount): string
    {
        $page = min($request->realblogPage(), $pageCount);
        $data = [
            "imageFolder" => $this->pluginFolder . "images/",
            "page" => $page,
            "prevPage" => max($page - 1, 1),
            "nextPage" => min($page + 1, $pageCount),
            "lastPage" => $pageCount,
            "articles" => $this->articleRecords($request, $articles, $page),
            "actionUrl" => $request->url()->withPage("")->relative(),
            "states" => $this->statusRecords($this->getFilterStatuses($request)),
            // "filters" => $this->getFilterStatuses($request),
        ];
        return $this->view->render("articles_form", $data);
    }

    /**
     * @param list<Article> $articles
     * @return list<array{id:int,date:string,status:int,categories:string,title:string,feedable:bool,commentable:bool,delete_url:string,edit_url:string}>
     */
    private function articleRecords(Request $request, array $articles, int $page)
    {
        $records = [];
        foreach ($articles as $article) {
            $url = $request->url()->withPage("realblog")->with("admin", "plugin_main")
                ->with("realblog_id", (string) $article->id)->with("realblog_page", (string) $page);
            $records[] = [
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
        }
        return $records;
    }

    /**
     * @param list<bool> $filters
     * @return list<array{value:int,label:string,checked:string}>
     */
    private function statusRecords(array $filters): array
    {
        return array_map(function (int $state, string $label) use ($filters) {
            return [
                "value" => $state,
                "label" => $label,
                "checked" => $filters[$state] ? "checked" : "",
            ];
        }, array_keys(self::STATES), array_values(self::STATES));
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
            $errors = [["story_added_error"]];
            return $this->showArticleEditor($article, "create", $errors);
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
            $errors = [["story_modified_error"]];
            return $this->showArticleEditor($article, "edit", $errors);
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    private function doDeleteAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = FullArticle::fromStrings(...$request->articlePost());
        $res = $this->db->deleteArticle($article);
        if ($res !== 1) {
            $errors = [["story_deleted_error"]];
            return $this->showArticleEditor($article, "delete", $errors);
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
            "states" => $this->stateRecords($article),
            "categories" => trim($article->categories, ","),
            "button" => $button,
            "errors" => $errors,
        ]);
    }

    /** @return list<array{value:int,label:string,selected:string}> */
    private function stateRecords(FullArticle $article): array
    {
        return array_map(function (int $status, string $label) use ($article) {
            return [
                "value" => $status,
                "label" => $label,
                "selected" => $status === $article->status ? "selected" : "",
            ];
        }, array_keys(self::STATES), array_values(self::STATES));
    }

    private function deleteSelectedAction(Request $request): Response
    {
        return Response::create($this->renderConfirmation($request, 'delete'))
            ->withTitle($this->view->text("tooltip_delete_selected"));
    }

    private function changeStatusAction(Request $request): Response
    {
        return Response::create($this->renderConfirmation($request, 'change_status'))
            ->withTitle($this->view->text("tooltip_change_status"));
    }

    private function doDeleteSelectedAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $ids = $request->realblogIdsFromGet();
        $res = $this->db->deleteArticlesWithIds($ids);
        if ($res !== count($ids)) {
            $errors = $res > 0
                ? [Html::of($this->view->message("warning", "deleteall_warning", $res, count($ids)))]
                : [Html::of($this->view->message("fail", "deleteall_error"))];
            return Response::create($this->renderConfirmation($request, "delete", $errors))
                ->withTitle($this->view->text("tooltip_delete_selected"));
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    private function doChangeStatusAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $ids = $request->realblogIdsFromGet();
        $status = $request->statusFromPost();
        $res = $this->db->updateStatusOfArticlesWithIds($ids, $status);
        if ($res !== count($ids)) {
            $errors = $res > 0
                ? [Html::of($this->view->message("warning", "changestatus_warning", $res, count($ids)))]
                : [Html::of($this->view->message("fail", "changestatus_error"))];
            return Response::create($this->renderConfirmation($request, "change_status", $errors))
                ->withTitle($this->view->text("tooltip_change_status"));
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    /** @param list<Html> $errors */
    private function renderConfirmation(Request $request, string $kind, array $errors = []): string
    {
        $data = [
            'ids' => $request->realblogIdsFromGet(),
            'url' => $this->overviewUrl($request)->relative(),
            'csrfToken' => $this->csrfProtector->token(),
            "errors" => $errors,
        ];
        if ($kind === 'change_status') {
            $data['states'] = self::STATES;
        }
        return $this->view->render("confirm_$kind", $data);
    }

    private function overviewUrl(Request $request): Url
    {
        return $request->url()->withPage("realblog")->with("admin", "plugin_main")->with("action", "plugin_text")
            ->with("realblog_page", (string) $request->realblogPage());
    }
}
