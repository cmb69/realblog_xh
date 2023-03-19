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
use Realblog\Infra\Url;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use Realblog\Value\Response;

class MainAdminController
{
    private const STATES = ['readyforpublishing', 'published', 'archived'];

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

    /** @var Request */
    private $request;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        DB $db,
        Finder $finder,
        CsrfProtector $csrfProtector,
        View $view,
        Editor $editor
    ) {
        $this->conf = $conf;
        $this->db = $db;
        $this->finder = $finder;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->editor = $editor;
    }

    public function __invoke(Request $request, string $action): Response
    {
        $this->request = $request;
        $response = $this->dispatch($action);
        if ($request->admin() && $request->edit() && $request->hasGet("realblog_page")) {
            $page = max($request->intFromGet("realblog_page"), 1);
            $response = $response->withCookie('realblog_page', (string) $page);
        }
        return $response;
    }

    private function dispatch(string $action): Response
    {
        switch ($action) {
            default:
                return $this->defaultAction();
            case "create":
                return $this->createAction();
            case "edit":
                return $this->editAction();
            case "delete":
                return $this->deleteAction();
            case "do_create":
                return $this->doCreateAction();
            case "do_edit":
                return $this->doEditAction();
            case "do_delete":
                return $this->doDeleteAction();
            case "delete_selected":
                return $this->deleteSelectedAction();
            case "change_status":
                return $this->changeStatusAction();
            case "do_delete_selected":
                return $this->doDeleteSelectedAction();
            case "do_change_status":
                return $this->doChangeStatusAction();
        }
    }

    private function defaultAction(): Response
    {
        $statuses = array_keys(array_filter($this->getFilterStatuses()));
        $total = $this->finder->countArticlesWithStatus($statuses);
        $limit = (int) $this->conf['admin_records_page'];
        $pageCount = (int) ceil($total / $limit);
        $page = min($this->request->realblogPage(), $pageCount);
        $offset = ($page - 1) * $limit;
        $articles = $this->finder->findArticlesWithStatus($statuses, $limit, $offset);
        $response = Response::create($this->renderArticles($articles, $pageCount));
        $filters = $this->request->filtersFromGet();
        if ($filters !== null && $filters !== $this->request->filtersFromCookie()) {
            $response = $response->withCookie("realblog_filter", (string) json_encode($filters));
        }
        return $response;
    }

    /** @return list<bool> */
    private function getFilterStatuses(): array
    {
        return $this->request->filtersFromGet() ?? $this->request->filtersFromCookie() ?? [false, false, false];
    }

    /** @param list<Article> $articles */
    private function renderArticles(array $articles, int $pageCount): string
    {
        $page = min($this->request->realblogPage(), $pageCount);
        $data = [
            "imageFolder" => $this->request->pluginsFolder() . "realblog/images/",
            "page" => $page,
            "prevPage" => max($page - 1, 1),
            "nextPage" => min($page + 1, $pageCount),
            "lastPage" => $pageCount,
            "articles" => $this->articleRecords($articles, $page),
            "actionUrl" => $this->request->url()->withPage("")->relative(),
            "states" => self::STATES,
            "filters" => $this->getFilterStatuses(),
        ];
        return $this->view->render("articles_form", $data);
    }

    /**
     * @param list<Article> $articles
     * @return list<array{id:int,date:string,status:int,categories:string,title:string,feedable:bool,commentable:bool,delete_url:string,edit_url:string}>
     */
    private function articleRecords(array $articles, int $page)
    {
        $records = [];
        foreach ($articles as $article) {
            $params = [
                "admin" => "plugin_main",
                "realblog_id" => (string) $article->id,
                "realblog_page" => (string) $page
            ];
            $records[] = [
                "id" => $article->id,
                "date" => $this->view->date($article->date),
                "status" => $article->status,
                "categories" => $article->categories,
                "title" => $article->title,
                "feedable" => $article->feedable,
                "commentable" => $article->commentable,
                "delete_url" => $this->request->url()->withPage("realblog")
                    ->withParams(["action" => "delete"] + $params)->relative(),
                "edit_url" => $this->request->url()->withPage("realblog")
                    ->withParams(["action" => "edit"] + $params)->relative(),
            ];
        }
        return $records;
    }

    private function createAction(): Response
    {
        $timestamp = $this->request->time();
        $article = new FullArticle(0, 0, $timestamp, 2147483647, 2147483647, 0, '', '', '', '', false, false);
        return $this->renderArticleForm($article, $this->view->text("tooltip_create"), "do_create", "btn_create");
    }

    private function editAction(): Response
    {
        $article = $this->finder->findById(max($this->request->intFromGet("realblog_id"), 1));
        if (!$article) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        return $this->renderArticleForm($article, $this->view->text("title_edit", $article->id), "do_edit", "btn_edit");
    }

    private function deleteAction(): Response
    {
        $article = $this->finder->findById(max($this->request->intFromGet("realblog_id"), 1));
        if (!$article) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        return $this->renderArticleForm($article, $this->view->text("title_delete", $article->id), "do_delete", "btn_delete");
    }

    private function renderArticleForm(FullArticle $article, string $title, string $action, string $button): Response
    {
        $this->editor->init(['realblog_headline_field', 'realblog_story_field']);
        $hjs = $this->view->renderMeta("realblog", $this->finder->findAllCategories());
        $bjs = $this->view->renderScript($this->request->pluginsFolder() . "realblog/realblog.js");
        return Response::create($this->view->render("article_form", [
            "article" => $article,
            "title" => $title,
            "date" => (string) date("Y-m-d", $article->date),
            "publishing_date" => (string) date("Y-m-d", $article->publishingDate),
            "archiving_date" => (string) date("Y-m-d", $article->archivingDate),
            "actionUrl" => $this->request->url()->withPage("realblog")
                ->withParams(["admin" => "plugin_main"])->relative(),
            "action" => $action,
            "csrfToken" => $this->csrfProtector->token(),
            "isAutoPublish" => (bool) $this->conf["auto_publish"],
            "isAutoArchive" => (bool) $this->conf["auto_archive"],
            "states" => self::STATES,
            "categories" => trim($article->categories, ","),
            "button" => $button,
        ]))->withTitle($title)->withHjs($hjs)->withBjs($bjs);
    }
    
    private function doCreateAction(): Response
    {
        $this->csrfProtector->check();
        $article = $this->request->articleFromPost();
        $res = $this->db->insertArticle($article);
        if ($res === 1) {
            return Response::redirect($this->overviewUrl()->absolute());
        }
        $info = $this->view->message("fail", "story_added_error");
        $output = $this->renderInfo("tooltip_create", $info);
        return Response::create($output)->withTitle($this->view->text("tooltip_create"));
    }

    private function doEditAction(): Response
    {
        $this->csrfProtector->check();
        $article = $this->request->articleFromPost();
        $res = $this->db->updateArticle($article);
        if ($res === 1) {
            return Response::redirect($this->overviewUrl()->absolute());
        }
        $info = $this->view->message("fail", "story_modified_error");
        $output = $this->renderInfo("tooltip_edit", $info);
        return Response::create($output)->withTitle($this->view->text("tooltip_edit"));
    }

    private function doDeleteAction(): Response
    {
        $this->csrfProtector->check();
        $article = $this->request->articleFromPost();
        $res = $this->db->deleteArticle($article);
        if ($res === 1) {
            return Response::redirect($this->overviewUrl()->absolute());
        }
        $info = $this->view->message("fail", "story_deleted_error");
        $output = $this->renderInfo("tooltip_delete", $info);
        return Response::create($output)->withTitle($this->view->text("tooltip_delete"));
    }

    private function deleteSelectedAction(): Response
    {
        return Response::create($this->renderConfirmation('delete'));
    }

    private function changeStatusAction(): Response
    {
        return Response::create($this->renderConfirmation('change_status'));
    }

    private function renderConfirmation(string $kind): string
    {
        $data = [
            'ids' => $this->request->realblogIdsFromGet(),
            'action' => $this->request->url()->withPage("realblog")
                ->withParams(["admin" => "plugin_main"])->relative(),
            'url' => $this->overviewUrl()->relative(),
            'csrfToken' => $this->csrfProtector->token(),
        ];
        if ($kind === 'change_status') {
            $data['states'] = self::STATES;
        }
        return $this->view->render("confirm_$kind", $data);
    }

    private function doDeleteSelectedAction(): Response
    {
        $this->csrfProtector->check();
        $ids = $this->request->realblogIdsFromPost();
        $res = $this->db->deleteArticlesWithIds($ids);
        if ($res === count($ids)) {
            return Response::redirect($this->overviewUrl()->absolute());
        }
        if ($res > 0) {
            $info = $this->view->message("warning", "deleteall_warning", $res, count($ids));
        } else {
            $info = $this->view->message("fail", "deleteall_error");
        }
        $output = $this->renderInfo("tooltip_delete_selected", $info);
        return Response::create($output)->withTitle($this->view->text("tooltip_delete_selected"));
    }

    private function doChangeStatusAction(): Response
    {
        $this->csrfProtector->check();
        $ids = $this->request->realblogIdsFromPost();
        $status = $this->request->statusFromPost();
        $res = $this->db->updateStatusOfArticlesWithIds($ids, $status);
        if ($res === count($ids)) {
            return Response::redirect($this->overviewUrl()->absolute());
        }
        if ($res > 0) {
            $info = $this->view->message("warning", "changestatus_warning", $res, count($ids));
        } else {
            $info = $this->view->message("fail", "changestatus_error");
        }
        $output = $this->renderInfo("tooltip_change_status", $info);
        return Response::create($output)->withTitle($this->view->text("tooltip_change_status"));
    }

    private function renderInfo(string $title, string $message): string
    {
        return $this->view->render("info_message", [
            "title" => $title,
            "message" => $message,
            "url" => $this->overviewUrl()->relative(),
        ]);
    }

    private function overviewUrl(): Url
    {
        return $this->request->url()->withPage("realblog")->withParams([
            "admin" => "plugin_main",
            "action" => "plugin_text",
            "realblog_page" => (string) $this->request->realblogPage()
        ]);
    }
}
