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

use Plib\CsrfProtector;
use Plib\Request;
use Plib\Response;
use Plib\Url;
use Plib\View;
use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Logic\Util;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class MainAdminController
{
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
        $articleCount = $this->finder->countArticles();
        $limit = (int) $this->conf['admin_records_page'];
        [$offset, $pageCount] = Util::paginationOffset($articleCount, $limit, $this->realblogPage($request));
        $articles = $this->finder->findAllArticles($limit, $offset);
        return Response::create($this->renderArticles($request, $articles, $pageCount));
    }

    /** @param list<Article> $articles */
    private function renderArticles(Request $request, array $articles, int $pageCount): string
    {
        $page = min($this->realblogPage($request), $pageCount);
        return $this->view->render("articles_form", [
            "imageFolder" => $this->pluginFolder . "images/",
            "page" => $page,
            "prevPage" => max($page - 1, 1),
            "nextPage" => min($page + 1, $pageCount),
            "lastPage" => $pageCount,
            "articles" => $this->articleRecords($request, $articles, $page),
        ]);
    }

    /**
     * @param list<Article> $articles
     * @return list<array{id:int,date:string,categories:string,title:string,commentable:bool,delete_url:string,edit_url:string}>
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
                "categories" => $article->categories,
                "title" => $article->title,
                "commentable" => $article->commentable,
                "delete_url" => $url->with("action", "delete")->relative(),
                "edit_url" => $url->with("action", "edit")->relative(),
            ];
        }, $articles);
    }

    private function createAction(Request $request): Response
    {
        $timestamp = $request->time();
        $article = new FullArticle(0, 0, $timestamp, '', '', '', '', false);
        return $this->showArticleEditor($request, $article, "create");
    }

    private function editAction(Request $request): Response
    {
        $article = $this->finder->findById(max((int) ($request->get("realblog_id") ?? 0), 1));
        if (!$article) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        return $this->showArticleEditor($request, $article, "edit");
    }

    private function doCreateAction(Request $request): Response
    {
        if (!$this->csrfProtector->check($request->post("realblog_token"))) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        $article = FullArticle::fromStrings(...$this->articlePost($request));
        $errors = Util::validateArticle($article);
        if ($errors) {
            return $this->showArticleEditor($request, $article, "create", $errors);
        }
        $res = $this->db->insertArticle($article);
        if ($res !== 1) {
            return $this->showArticleEditor($request, $article, "create", [["story_added_error"]]);
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    private function doEditAction(Request $request): Response
    {
        if (!$this->csrfProtector->check($request->post("realblog_token"))) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        $article = FullArticle::fromStrings(...$this->articlePost($request));
        $errors = Util::validateArticle($article);
        if ($errors) {
            return $this->showArticleEditor($request, $article, "edit", $errors);
        }
        $res = $this->db->updateArticle($article);
        if ($res !== 1) {
            return $this->showArticleEditor($request, $article, "edit", [["story_modified_error"]]);
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    /** @param list<array{string}> $errors */
    private function showArticleEditor(
        Request $request,
        FullArticle $article,
        string $action,
        array $errors = []
    ): Response {
        assert(in_array($action, ["create", "edit"], true));
        if ($action === "create") {
            $title = $this->view->text("tooltip_create");
        } elseif ($action === "edit") {
            $title = $this->view->text("title_edit", $article->id);
        }
        $this->editor->init(['realblog_headline_field', 'realblog_story_field']);
        $json = json_encode(
            $this->finder->findAllCategories(),
            JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $hjs = "<meta name=\"realblog\" content='$json'>\n";
        return Response::create($this->renderArticleForm($request, $article, $title, "btn_$action", $errors))
            ->withTitle($title)->withHjs($hjs);
    }

    /** @param list<array{string}> $errors */
    private function renderArticleForm(
        Request $request,
        FullArticle $article,
        string $title,
        string $button,
        array $errors
    ): string {
        return $this->view->render("article_form", [
            "id" => $article->id,
            "version" => $article->version,
            "title" => $article->title,
            "teaser" => $article->teaser,
            "body" => $article->body,
            "commentable" => $article->commentable ? "checked" : "",
            "page_title" => $title,
            "date" => (string) date("Y-m-d", $article->date),
            "csrfToken" => $this->csrfProtector->token(),
            "categories" => trim($article->categories, ","),
            "button" => $button,
            "errors" => $errors,
            "script" => $this->pluginFolder . "realblog.js",
            "back_url" => $request->url()->without("action")->relative(),
        ]);
    }

    /** @return array{string,string,string,string,string,string,string,string} */
    private function articlePost(Request $request): array
    {
        return [
            $request->post("realblog_id") ?? "",
            $request->post("realblog_version") ?? "",
            $request->post("realblog_date") ?? "",
            $request->post("realblog_categories") ?? "",
            $request->post("realblog_title") ?? "",
            $request->post("realblog_headline") ?? "",
            $request->post("realblog_story") ?? "",
            $request->post("realblog_comments") ?? "",
        ];
    }

    private function deleteAction(Request $request): Response
    {
        return Response::create($this->renderDeleteConfirmation($request))
            ->withTitle($this->view->text("tooltip_delete"));
    }

    private function doDeleteAction(Request $request): Response
    {
        if (!$this->csrfProtector->check($request->post("realblog_token"))) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        $id = (int) $request->get("realblog_id");
        $res = $this->db->deleteArticleById($id);
        if (!$res) {
            return Response::create($this->renderDeleteConfirmation($request, [["delete_error"]]))
                ->withTitle($this->view->text("tooltip_delete"));
        }
        return Response::redirect($this->overviewUrl($request)->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderDeleteConfirmation(Request $request, array $errors = []): string
    {
        return $this->view->render("confirm_delete", [
            "id" => $request->get("realblog_id"),
            "url" => $this->overviewUrl($request)->relative(),
            "csrfToken" => $this->csrfProtector->token(),
            "errors" => $errors,
        ]);
    }

    private function overviewUrl(Request $request): Url
    {
        return $request->url()->page("realblog")->with("admin", "plugin_main")->with("action", "plugin_text")
            ->with("realblog_page", (string) $this->realblogPage($request));
    }

    private function realblogPage(Request $request): int
    {
        return max(1, (int) $request->get("realblog_page"));
    }
}
