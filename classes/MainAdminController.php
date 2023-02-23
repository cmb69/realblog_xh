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

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

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
    }

    public function __invoke(Request $request, string $action): Response
    {
        $this->request = $request;
        $this->response = new Response;
        $this->page = $this->getPage();
        switch ($action) {
            default:
                $this->defaultAction();
                break;
            case "create":
                $this->createAction();
                break;
            case "edit":
                $this->editAction();
                break;
            case "delete":
                $this->deleteAction();
                break;
            case "do_create":
                $this->doCreateAction();
                break;
            case "do_edit":
                $this->doEditAction();
                break;
            case "do_delete":
                $this->doDeleteAction();
                break;
            case "delete_selected":
                $this->deleteSelectedAction();
                break;
            case "change_status":
                $this->changeStatusAction();
                break;
            case "do_delete_selected":
                $this->doDeleteSelectedAction();
                break;
            case "do_change_status":
                $this->doChangeStatusAction();
                break;
        }
        return $this->response;
    }

    private function getPage(): int
    {
        if ($this->request->admin() && $this->request->edit()) {
            if (isset($_GET['realblog_page'])) {
                $page = max((int) ($_GET['realblog_page'] ?? 1), 1);
                $_COOKIE['realblog_page'] = $page;
                setcookie('realblog_page', (string) $page, 0, CMSIMPLE_ROOT);
            } else {
                $page = max((int) ($_COOKIE['realblog_page'] ?? 1), 1);
            }
        } else {
            $page = max((int) ($_GET['realblog_page'] ?? 1), 1);
        }
        return $page;
    }

    /** @return void */
    private function defaultAction()
    {
        for ($i = 0; $i <= 2; $i++) {
            $varname = "realblog_filter$i";
            if (isset($_GET[$varname]) && !isset($_COOKIE[$varname])) {
                $this->response->addCookie($varname, $_GET[$varname] ? "on" : "");
            }
        }
        $statuses = $this->getFilterStatuses();
        $total = $this->finder->countArticlesWithStatus($statuses);
        $limit = (int) $this->conf['admin_records_page'];
        $pageCount = (int) ceil($total / $limit);
        $page = max(min($this->page, $pageCount), 1);
        $offset = ($page - 1) * $limit;
        $articles = $this->finder->findArticlesWithStatus($statuses, $limit, $offset);
        $this->response->setOutput($this->renderArticles($articles, $pageCount));
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
    private function renderArticles(array $articles, int $pageCount): string
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
                "delete_url" => $this->request->url()->withPage("realblog")
                    ->withParams(["action" => "delete"] + $params)->relative(),
                "edit_url" => $this->request->url()->withPage("realblog")
                    ->withParams(["action" => "edit"] + $params)->relative(),
            ];
        }
        $data = [
            'imageFolder' => $this->request->pluginsFolder() . "realblog/images/",
            'page' => $page,
            'prevPage' => max($page - 1, 1),
            'nextPage' => min($page + 1, $pageCount),
            'lastPage' => $pageCount,
            'articles' => $records,
            'actionUrl' => $this->request->url()->withPage("")->relative(),
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

    /** @return void */
    private function createAction()
    {
        $this->renderArticle('create');
    }

    /** @return void */
    private function editAction()
    {
        $this->renderArticle('edit');
    }

    /** @return void */
    private function deleteAction()
    {
        $this->renderArticle('delete');
    }

    /** @return void */
    private function renderArticle(string $action)
    {
        $this->editor->init(['realblog_headline_field', 'realblog_story_field']);
        if ($action === 'create') {
            $article = new FullArticle(0, 0, $this->request->time(), 2147483647, 2147483647, 0, '', '', '', '', false, false);
        } else {
            $id = max($_GET['realblog_id'] ?? 1, 1);
            $article = $this->finder->findById($id);
            if (!$article) {
                $this->response->setOutput($this->view->message("fail", "message_not_found"));
                return;
            }
        }
        $this->renderForm($article, $action);
    }

    /** @return void */
    private function renderForm(FullArticle $article, string $action)
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
        $hjs = $this->useCalendar();
        $bjs = '<script>REALBLOG.categories = '
            . json_encode($this->finder->findAllCategories()) . ';</script>' . "\n"
            . '<script src="' . $this->request->pluginsFolder()
            . 'realblog/realblog.js"></script>';
        $data = [
            'article' => $article,
            'title' => $title,
            'date' => (string) date('Y-m-d', $article->date),
            'publishing_date' => (string) date('Y-m-d', $article->publishingDate),
            'archiving_date' => (string) date('Y-m-d', $article->archivingDate),
            'actionUrl' => $this->request->url()->withPage("realblog")->withParams(["admin" => "plugin_main"])->relative(),
            'action' => "do_{$action}",
            'csrfToken' => $this->getCsrfToken(),
            'calendarIcon' => $this->request->pluginsFolder() . "realblog/images/calendar.png",
            'isAutoPublish' => $this->conf['auto_publish'],
            'isAutoArchive' => $this->conf['auto_archive'],
            'states' => array('readyforpublishing', 'published', 'archived'),
            'categories' => trim($article->categories, ','),
            'button' => "btn_{$action}",
        ];
        $this->response->setOutput($this->view->render('article-form', $data))
            ->setTitle($title)->setHjs($hjs)->setBjs($bjs);
    }

    private function useCalendar(): string
    {
        $calendarFolder = $this->request->pluginsFolder() . 'realblog/jscalendar/';
        $stylesheet = $calendarFolder . 'calendar-system.css';
        $mainScript = $calendarFolder . 'calendar.js';
        $languageScript = $calendarFolder . 'lang/calendar-' . $this->request->language() . '.js';
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

    /** @return void */
    private function doCreateAction()
    {
        $this->csrfProtector->check();
        $article = $this->getArticleFromParameters();
        $res = $this->db->insertArticle($article);
        if ($res === 1) {
            $this->redirectToOverviewResponse($this->request->url());
            return;
        } else {
            $info = $this->view->message("fail", "story_added_error");
        }
        $output = $this->renderInfo($this->request->url(), "tooltip_create", $info);
        $this->response->setOutput($output)->setTitle($this->view->text("tooltip_create"));
    }

    /** @return void */
    private function doEditAction()
    {
        $this->csrfProtector->check();
        $article = $this->getArticleFromParameters();
        $res = $this->db->updateArticle($article);
        if ($res === 1) {
            $this->redirectToOverviewResponse($this->request->url());
            return;
        } else {
            $info = $this->view->message("fail", "story_modified_error");
        }
        $output = $this->renderInfo($this->request->url(), "tooltip_edit", $info);
        $this->response->setOutput($output)->setTitle($this->view->text("tooltip_edit"));
    }

    /** @return void */
    private function doDeleteAction()
    {
        $this->csrfProtector->check();
        $article = $this->getArticleFromParameters();
        $res = $this->db->deleteArticle($article);
        if ($res === 1) {
            $this->redirectToOverviewResponse($this->request->url());
            return;
        } else {
            $info = $this->view->message("fail", "story_deleted_error");
        }
        $output = $this->renderInfo($this->request->url(), "tooltip_delete", $info);
        $this->response->setOutput($output)->setTitle($this->view->text("tooltip_delete"));
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

    /** @return void */
    private function deleteSelectedAction()
    {
        $this->response->setOutput($this->renderConfirmation('delete'));
    }

    /** @return void */
    private function changeStatusAction()
    {
        $this->response->setOutput($this->renderConfirmation('change-status'));
    }

    private function renderConfirmation(string $kind): string
    {
        $data = [
            'ids' => array_filter($_GET["realblog_ids"] ?? [], function ($id) {
                return (int) $id >= 1;
            }),
            'action' => $this->request->url()->withPage("realblog")->withParams(["admin" => "plugin_main"])->relative(),
            'url' => $this->request->url()->withPage("realblog")
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

    /** @return void */
    private function doDeleteSelectedAction()
    {
        $this->csrfProtector->check();
        $ids = array_filter($_POST["realblog_ids"] ?? [], function ($id) {
            return (int) $id >= 1;
        });
        $res = $this->db->deleteArticlesWithIds($ids);
        if ($res === count($ids)) {
            $this->redirectToOverviewResponse($this->request->url());
            return;
        } elseif ($res > 0) {
            $info = $this->view->message("warning", "deleteall_warning", $res, count($ids));
        } else {
            $info = $this->view->message("fail", "deleteall_error");
        }
        $output = $this->renderInfo($this->request->url(), "tooltip_delete_selected", $info);
        $this->response->setOutput($output)->setTitle($this->view->text("tooltip_delete_selected"));
    }

    /** @return void */
    private function doChangeStatusAction()
    {
        $this->csrfProtector->check();
        $ids = array_filter($_POST["realblog_ids"] ?? [], function ($id) {
            return (int) $id >= 1;
        });
        $status = min(max((int) ($_POST["realblog_status"] ?? 0), 0), 2);
        $res = $this->db->updateStatusOfArticlesWithIds($ids, $status);
        if ($res === count($ids)) {
            $this->redirectToOverviewResponse($this->request->url());
            return;
        } elseif ($res > 0) {
            $info = $this->view->message("warning", "changestatus_warning", $res, count($ids));
        } else {
            $info = $this->view->message("fail", "changestatus_error");
        }
        $output = $this->renderInfo($this->request->url(), "tooltip_change_status", $info);
        $this->response->setOutput($output)->setTitle($this->view->text("tooltip_change_status"));
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

    /** @return void */
    private function redirectToOverviewResponse(Url $url)
    {
        $params = ["admin" => "plugin_main", "action" => "plugin_text", "realblog_page" => (string) $this->page];
        $this->response->redirect($url->withPage("realblog")->withParams($params));
    }
}
