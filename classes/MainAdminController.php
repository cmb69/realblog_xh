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

use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Infra\Request;
use Realblog\Infra\Response;
use Realblog\Infra\Url;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use RuntimeException;
use XH\CSRFProtection as CsrfProtector;

class MainAdminController
{
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

    /** @var int */
    private $page;

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
        $this->page = Plugin::getPage();
    }

    public function __invoke(Request $request, string $action): Response
    {
        switch ($action) {
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
        $response = new Response;
        for ($i = 0; $i <= 2; $i++) {
            $varname = "realblog_filter$i";
            if (isset($_GET[$varname]) && !isset($_COOKIE[$varname])) {
                $response = $response->withCookie($varname, $_GET[$varname] ? "on" : "");
            }
        }
        $statuses = $this->getFilterStatuses();
        $total = $this->finder->countArticlesWithStatus($statuses);
        $limit = (int) $this->conf['admin_records_page'];
        $pageCount = (int) ceil($total / $limit);
        $page = max(min($this->page, $pageCount), 1);
        $offset = ($page - 1) * $limit;
        $articles = $this->finder->findArticlesWithStatus($statuses, $limit, $offset);
        return $response->withOutput($this->renderArticles($request, $articles, $pageCount));
    }

    /** @return list<int> */
    private function getFilterStatuses(): array
    {
        $statuses = array();
        for ($i = 0; $i <= 2; $i++) {
            if ($this->getFilter($i)) {
                $statuses[] = $i;
            }
        }
        return $statuses;
    }

    /** @param list<Article> $articles */
    private function renderArticles(Request $request, array $articles, int $pageCount): string
    {
        $states = ['readyforpublishing', 'published', 'archived'];
        $filters = [];
        foreach (array_keys($states) as $i) {
            $filters[] = $this->getFilter($i);
        }
        $page = min(max($this->page, 0), $pageCount);
        $records = [];
        foreach ($articles as $article) {
            $params = ["admin" => "plugin_main", "realblog_id" => (string) $article->id, "realblog_page" => (string) $page];
            $records[] = [
                "id" => $article->id,
                "date" => $this->view->date($article->date),
                "status" => $article->status,
                "categories" => $article->categories,
                "title" => $article->title,
                "feedable" => $article->feedable,
                "commentable" => $article->commentable,
                "delete_url" => $request->url()->withPage("realblog")
                    ->withParams(["action" => "delete"] + $params)->relative(),
                "edit_url" => $request->url()->withPage("realblog")
                    ->withParams(["action" => "edit"] + $params)->relative(),
            ];
        }
        $data = [
            'imageFolder' => $request->pluginsFolder() . "realblog/images/",
            'page' => $page,
            'prevPage' => max($page - 1, 1),
            'nextPage' => min($page + 1, $pageCount),
            'lastPage' => $pageCount,
            'articles' => $records,
            'actionUrl' => $request->url()->withPage("")->relative(),
            'states' => $states,
            'filters' => $filters,
        ];
        return $this->view->render('articles-form', $data);
    }

    private function getFilter(int $num): bool
    {
        $varname = "realblog_filter$num";
        if (isset($_GET[$varname])) {
            return (bool) ($_GET[$varname] ?? false);
        }
        return (bool) ($_COOKIE[$varname] ?? false);
    }

    private function createAction(Request $request): Response
    {
        return $this->renderArticle($request, 'create');
    }

    private function editAction(Request $request): Response
    {
        return $this->renderArticle($request, 'edit');
    }

    private function deleteAction(Request $request): Response
    {
        return $this->renderArticle($request, 'delete');
    }

    private function renderArticle(Request $request, string $action): Response
    {
        $this->editor->init(['realblog_headline_field', 'realblog_story_field']);
        if ($action === 'create') {
            $article = new FullArticle(0, 0, $request->time(), 2147483647, 2147483647, 0, '', '', '', '', false, false);
        } else {
            $id = max($_GET['realblog_id'] ?? 1, 1);
            $article = $this->finder->findById($id);
            if (!$article) {
                return (new Response)->withOutput($this->view->message("fail", "message_not_found"));
            }
        }
        return $this->renderForm($article, $request, $action);
    }

    private function renderForm(FullArticle $article, Request $request, string $action): Response
    {
        switch ($action) {
            case 'create':
                $title = $this->view->text("tooltip_create");
                break;
            case 'edit':
                $title = $this->view->text("title_edit", $article->id);
                break;
            case 'delete':
                $title = $this->view->text("title_delete", $article->id);
                break;
            default:
                throw new RuntimeException("Unsupported action");
        }
        $hjs = $this->useCalendar($request);
        $bjs = '<script>REALBLOG.categories = '
            . json_encode($this->finder->findAllCategories()) . ';</script>' . "\n"
            . '<script src="' . $request->pluginsFolder()
            . 'realblog/realblog.js"></script>';
        $data = [
            'article' => $article,
            'title' => $title,
            'date' => (string) date('Y-m-d', $article->date),
            'publishing_date' => (string) date('Y-m-d', $article->publishingDate),
            'archiving_date' => (string) date('Y-m-d', $article->archivingDate),
            'actionUrl' => $request->url()->withPage("realblog")->withParams(["admin" => "plugin_main"])->relative(),
            'action' => "do_{$action}",
            'csrfToken' => $this->getCsrfToken(),
            'calendarIcon' => $request->pluginsFolder() . "realblog/images/calendar.png",
            'isAutoPublish' => $this->conf['auto_publish'],
            'isAutoArchive' => $this->conf['auto_archive'],
            'states' => array('readyforpublishing', 'published', 'archived'),
            'categories' => trim($article->categories, ','),
            'button' => "btn_{$action}",
        ];
        return (new Response)->withOutput($this->view->render('article-form', $data))
            ->withTitle($title)->withHjs($hjs)->withBjs($bjs);
    }

    private function useCalendar(Request $request): string
    {
        $calendarFolder = $request->pluginsFolder() . 'realblog/jscalendar/';
        $stylesheet = $calendarFolder . 'calendar-system.css';
        $mainScript = $calendarFolder . 'calendar.js';
        $languageScript = $calendarFolder . 'lang/calendar-' . $request->language() . '.js';
        if (!file_exists($languageScript)) {
            $languageScript = $calendarFolder . 'lang/calendar-en.js';
        }
        $setupScript = $calendarFolder . 'calendar-setup.js';
        return <<<EOT
<script>/* <![CDATA[ */
var REALBLOG = REALBLOG || {};
(function () {
    var input = document.createElement("input");
    input.setAttribute("type", "date");
    REALBLOG.hasNativeDatePicker = (input.type == "date");
    if (!REALBLOG.hasNativeDatePicker) {
        document.write(
            '<link rel="stylesheet" type="text/css" href="$stylesheet">' +
            '<script src="$mainScript"><\/script>' +
            '<script src="$languageScript"><\/script>' +
            '<script src="$setupScript"><\/script>'
        );
    }
}());
/* ]]> */</script>
EOT;
    }

    private function doCreateAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = $this->getArticleFromParameters();
        $res = $this->db->insertArticle($article);
        if ($res === 1) {
            return $this->redirectToOverviewResponse($request->url());
        } else {
            $info = $this->view->message("fail", "story_added_error");
        }
        $output = $this->renderInfo($request->url(), "tooltip_create", $info);
        return (new Response)->withOutput($output)->withTitle($this->view->text("tooltip_create"));
    }

    private function doEditAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = $this->getArticleFromParameters();
        $res = $this->db->updateArticle($article);
        if ($res === 1) {
            return $this->redirectToOverviewResponse($request->url());
        } else {
            $info = $this->view->message("fail", "story_modified_error");
        }
        $output = $this->renderInfo($request->url(), "tooltip_edit", $info);
        return (new Response)->withOutput($output)->withTitle($this->view->text("tooltip_edit"));
    }

    private function doDeleteAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $article = $this->getArticleFromParameters();
        $res = $this->db->deleteArticle($article);
        if ($res === 1) {
            return $this->redirectToOverviewResponse($request->url());
        } else {
            $info = $this->view->message("fail", "story_deleted_error");
        }
        $output = $this->renderInfo($request->url(), "tooltip_delete", $info);
        return (new Response)->withOutput($output)->withTitle($this->view->text("tooltip_delete"));
    }

    private function getArticleFromParameters(): FullArticle
    {
        return new FullArticle(
            (int) $_POST['realblog_id'],
            (int) $_POST['realblog_version'],
            !isset($_POST['realblog_date_exact']) || $_POST['realblog_date'] !== $_POST['realblog_date_old']
                ? $this->stringToTime($_POST['realblog_date'], true)
                : $_POST['realblog_date_exact'],
            $this->stringToTime($_POST['realblog_startdate']),
            $this->stringToTime($_POST['realblog_enddate']),
            (int) $_POST['realblog_status'],
            ',' . trim($_POST['realblog_categories']) . ',',
            $_POST['realblog_title'],
            $_POST['realblog_headline'],
            $_POST['realblog_story'],
            isset($_POST['realblog_rssfeed']),
            isset($_POST['realblog_comments'])
        );
    }

    private function stringToTime(string $date, bool $withTime = false): int
    {
        $parts = explode('-', $date);
        if ($withTime) {
            $timestamp = getdate();
        } else {
            $timestamp = array('hours' => 0, 'minutes' => 0, 'seconds' => 0);
        }
        return (int) mktime(
            $timestamp['hours'],
            $timestamp['minutes'],
            $timestamp['seconds'],
            (int) $parts[1],
            (int) $parts[2],
            (int) $parts[0]
        );
    }

    private function deleteSelectedAction(Request $request): Response
    {
        return (new Response)->withOutput($this->renderConfirmation($request, 'delete'));
    }

    private function changeStatusAction(Request $request): Response
    {
        return (new Response)->withOutput($this->renderConfirmation($request, 'change-status'));
    }

    private function renderConfirmation(Request $request, string $kind): string
    {
        $data = [
            'ids' => array_filter($_GET["realblog_ids"] ?? [], function ($id) {
                return (int) $id >= 1;
            }),
            'action' => $request->url()->withPage("realblog")->withParams(["admin" => "plugin_main"])->relative(),
            'url' => $request->url()->withPage("realblog")
                ->withParams(["admin" => "plugin_main", "action" => "plugin_text", "realblog_page" => (string) $this->page])
                ->relative(),
            'csrfToken' => $this->getCsrfToken(),
        ];
        if ($kind === 'change-status') {
            $data['states'] = array(
                'new_realblogstatus', 'readyforpublishing', 'published', 'archived'
            );
        }
        return $this->view->render("confirm-$kind", $data);
    }

    private function doDeleteSelectedAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $ids = array_filter($_POST["realblog_ids"] ?? [], function ($id) {
            return (int) $id >= 1;
        });
        $res = $this->db->deleteArticlesWithIds($ids);
        if ($res === count($ids)) {
            return $this->redirectToOverviewResponse($request->url());
        } elseif ($res > 0) {
            $info = $this->view->message("warning", "deleteall_warning", $res, count($ids));
        } else {
            $info = $this->view->message("fail", "deleteall_error");
        }
        $output = $this->renderInfo($request->url(), "tooltip_delete_selected", $info);
        return (new Response)->withOutput($output)->withTitle($this->view->text("tooltip_delete_selected"));
    }

    private function doChangeStatusAction(Request $request): Response
    {
        $this->csrfProtector->check();
        $ids = array_filter($_POST["realblog_ids"] ?? [], function ($id) {
            return (int) $id >= 1;
        });
        $status = min(max((int) ($_POST["realblog_status"] ?? 0), 0), 2);
        $res = $this->db->updateStatusOfArticlesWithIds($ids, $status);
        if ($res === count($ids)) {
            return $this->redirectToOverviewResponse($request->url());
        } elseif ($res > 0) {
            $info = $this->view->message("warning", "changestatus_warning", $res, count($ids));
        } else {
            $info = $this->view->message("fail", "changestatus_error");
        }
        $output = $this->renderInfo($request->url(), "tooltip_change_status", $info);
        return (new Response)->withOutput($output)->withTitle($this->view->text("tooltip_change_status"));
    }

    private function getCsrfToken(): ?string
    {
        $html = $this->csrfProtector->tokenInput();
        if (preg_match('/value="([0-9a-f]+)"/', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function renderInfo(Url $url, string $title, string $message): string
    {
        $params = ["admin" => "plugin_main", "action" => "plugin_text", "realblog_page" => (string) $this->page];

        return $this->view->render("info_message", [
            "title" => $title,
            "message" => $message,
            "url" => $url->withPage("realblog")->withParams($params)->relative(),
        ]);
    }

    private function redirectToOverviewResponse(Url $url): Response
    {
        $params = ["admin" => "plugin_main", "action" => "plugin_text", "realblog_page" => (string) $this->page];
        return (new Response)->redirect($url->withPage("realblog")->withParams($params));
    }
}
