<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
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

use stdClass;

class MainAdminController
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $text;

    /** @var string */
    private $urlPath;

    /** @var int */
    private $page;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $text
     */
    public function __construct(array $config, array $text)
    {
        global $sn;

        $this->config = $config;
        $this->text = $text;
        $this->urlPath = $sn;
        $this->page = Realblog::getPage();
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        $statuses = $this->getFilterStatuses();
        $total = Finder::countArticlesWithStatus($statuses);
        $limit = (int) $this->config['admin_records_page'];
        $pageCount = (int) ceil($total / $limit);
        $page = max(min($this->page, $pageCount), 1);
        $offset = ($page - 1) * $limit;
        $articles = Finder::findArticlesWithStatus($statuses, $limit, $offset);
        return $this->renderArticles($articles, $pageCount);
    }

    /**
     * @return int[]
     */
    private function getFilterStatuses()
    {
        $statuses = array();
        for ($i = 0; $i <= 2; $i++) {
            if (Realblog::getFilter($i)) {
                $statuses[] = $i;
            }
        }
        return $statuses;
    }

    /**
     * @param stdClass[] $articles
     * @param int $pageCount
     * @return string
     */
    private function renderArticles($articles, $pageCount)
    {
        global $pth;

        $view = new View('articles-form');
        $view->imageFolder = "{$pth['folder']['plugins']}realblog/images/";
        $view->page = $page = min(max($this->page, 0), $pageCount);
        $view->prevPage = max($page - 1, 1);
        $view->nextPage = min($page + 1, $pageCount);
        $view->lastPage = $pageCount;
        $view->articles = $articles;
        $view->actionUrl = $this->urlPath;
        $view->deleteUrl =
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) use ($page) {
                global $sn;

                return "$sn?&realblog&admin=plugin_main&action=delete"
                    . "&realblog_id={$article->id}&realblog_page=$page";
            };
        $view->editUrl =
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) use ($page) {
                global $sn;

                return "$sn?&realblog&admin=plugin_main&action=edit"
                    . "&realblog_id={$article->id}&realblog_page=$page";
            };
        $view->states = array('readyforpublishing', 'published', 'archived');
        $view->hasFilter =
            /**
             * @param int $num
             * @return bool
             */
            function ($num) {
                return Realblog::getFilter($num);
            };
        $view->formatDate =
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) {
                return (string) date($this->text['date_format'], $article->date);
            };
        return $view->render();
    }

    /**
     * @return string
     */
    public function createAction()
    {
        return $this->renderArticle('create');
    }

    /**
     * @return string
     */
    public function editAction()
    {
        return $this->renderArticle('edit');
    }

    /**
     * @return string
     */
    public function deleteAction()
    {
        return $this->renderArticle('delete');
    }

    /**
     * @param string $action
     * @return string
     */
    private function renderArticle($action)
    {

        init_editor(array('realblog_headline_field', 'realblog_story_field'));
        if ($action === 'create') {
            $article = $this->makeArticle();
        } else {
            $id = filter_input(
                INPUT_GET,
                'realblog_id',
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1))
            );
            $article = Finder::findById($id);
            if (!$article) {
                return XH_message('fail', $this->text['message_not_found']);
            }
        }
        return $this->renderForm($article, $action);
    }

    /**
     * @return stdClass
     */
    private function makeArticle()
    {
        return (object) array(
            'id' => null,
            'version' => 0,
            'date' => time(),
            'publishing_date' => 2147483647,
            'archiving_date' => 2147483647,
            'status' => 0,
            'categories' => '',
            'title' => '',
            'teaser' => '',
            'body' => '',
            'feedable' => 0,
            'commentable' => 0
        );
    }

    /**
     * @param string $action
     * @return string
     */
    private function renderForm(stdClass $article, $action)
    {
        global $pth, $sn, $title, $bjs;

        switch ($action) {
            case 'create':
                $title = $this->text['tooltip_create'];
                break;
            case 'edit':
                $title = "{$this->text['tooltip_edit']} #{$article->id}";
                break;
            case 'delete':
                $title = "{$this->text['tooltip_delete']} #{$article->id}";
                break;
            default:
                assert(false);
        }
        $this->useCalendar();
        $bjs .= '<script>REALBLOG.categories = '
            . json_encode(Finder::findAllCategories()) . ';</script>'
            . '<script src="' . $pth['folder']['plugins']
            . 'realblog/realblog.js"></script>';
        $view = new View('article-form');
        $view->article = $article;
        $view->title = $title;
        $view->actionUrl = "$sn?&realblog&admin=plugin_main";
        $view->action = "do_{$action}";
        $view->csrfToken = $this->getCsrfToken();
        $view->calendarIcon = "{$pth['folder']['plugins']}realblog/images/calendar.png";
        $view->formatDate =
            /**
             * @param int $time
             * @return string
             */
            function ($time) {
                return (string) date('Y-m-d', $time);
            };
        $view->isAutoPublish = $this->config['auto_publish'];
        $view->isAutoArchive = $this->config['auto_archive'];
        $view->states = array('readyforpublishing', 'published', 'archived');
        $view->categories = trim($article->categories, ',');
        $view->button = "btn_{$action}";
        return $view->render();
    }

    /**
     * @return void
     */
    private function useCalendar()
    {
        global $pth, $sl, $hjs;

        $calendarFolder = $pth['folder']['plugins'] . 'realblog/jscalendar/';
        $stylesheet = $calendarFolder . 'calendar-system.css';
        $mainScript = $calendarFolder . 'calendar.js';
        $languageScript = $calendarFolder . 'lang/calendar-' . $sl . '.js';
        if (!file_exists($languageScript)) {
            $languageScript = $calendarFolder . 'lang/calendar-en.js';
        }
        $setupScript = $calendarFolder . 'calendar-setup.js';
        $hjs .= <<<EOT
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

    /**
     * @return string
     */
    public function doCreateAction()
    {
        global $title;

        $this->checkCsrfToken();
        $article = $this->getArticleFromParameters();
        $res = DB::insertArticle($article);
        if ($res === 1) {
            $this->redirectToOverview();
        } else {
            $info = XH_message('fail', $this->text['story_added_error']);
        }
        $title = $this->text['tooltip_create'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return string
     */
    public function doEditAction()
    {
        global $title;

        $this->checkCsrfToken();
        $article = $this->getArticleFromParameters();
        $res = DB::updateArticle($article);
        if ($res === 1) {
            $this->redirectToOverview();
        } else {
            $info = XH_message('fail', $this->text['story_modified_error']);
        }
        $title = $this->text['tooltip_edit'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return string
     */
    public function doDeleteAction()
    {
        global $title;

        $this->checkCsrfToken();
        $article = $this->getArticleFromParameters();
        $res = DB::deleteArticle($article);
        if ($res === 1) {
            $this->redirectToOverview();
        } else {
            $info = XH_message('fail', $this->text['story_deleted_error']);
        }
        $title = $this->text['tooltip_delete'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return stdClass
     */
    private function getArticleFromParameters()
    {
        $article = new stdClass();
        $article->id = $_POST['realblog_id'];
        $article->version = $_POST['realblog_version'];
        if (!isset($_POST['realblog_date_exact']) || $_POST['realblog_date'] !== $_POST['realblog_date_old']) {
            $article->date = $this->stringToTime($_POST['realblog_date'], true);
        } else {
            $article->date = $_POST['realblog_date_exact'];
        }
        $article->title = $_POST['realblog_title'];
        $article->teaser = $_POST['realblog_headline'];
        $article->body = $_POST['realblog_story'];
        $article->publishing_date = $this->stringToTime($_POST['realblog_startdate']);
        $article->archiving_date = $this->stringToTime($_POST['realblog_enddate']);
        $article->status = $_POST['realblog_status'];
        $article->feedable = isset($_POST['realblog_rssfeed']);
        $article->commentable = isset($_POST['realblog_comments']);
        $article->categories = ',' . trim($_POST['realblog_categories']) . ',';
        return $article;
    }

    /**
     * @param string $date
     * @param bool $withTime
     * @return int
     */
    private function stringToTime($date, $withTime = false)
    {
        $parts = explode('-', $date);
        if ($withTime) {
            $timestamp = getdate();
        } else {
            $timestamp = array('hours' => 0, 'minutes' => 0, 'seconds' => 0);
        }
        return mktime(
            $timestamp['hours'],
            $timestamp['minutes'],
            $timestamp['seconds'],
            (int) $parts[1],
            (int) $parts[2],
            (int) $parts[0]
        );
    }

    /**
     * @return string
     */
    public function deleteSelectedAction()
    {
        return $this->renderConfirmation('delete');
    }

    /**
     * @return string
     */
    public function changeStatusAction()
    {
        return $this->renderConfirmation('change-status');
    }

    /**
     * @param string $kind
     * @return string
     */
    private function renderConfirmation($kind)
    {
        $view = new View("confirm-$kind");
        if ($kind === 'change-status') {
            $view->states = array(
                'new_realblogstatus', 'readyforpublishing', 'published', 'archived'
            );
        }
        $view->ids = filter_input(
            INPUT_GET,
            'realblog_ids',
            FILTER_VALIDATE_INT,
            array(
                'flags' => FILTER_REQUIRE_ARRAY,
                'options' => array('min_range' => 1)
            )
        );
        $view->action = "{$this->urlPath}?&realblog&admin=plugin_main";
        $view->url = "{$this->urlPath}?&realblog&admin=plugin_main&action=plugin_text&realblog_page={$this->page}";
        $view->csrfToken = $this->getCsrfToken();
        return $view->render();
    }

    /**
     * @return string
     */
    public function doDeleteSelectedAction()
    {
        global $title;

        $this->checkCsrfToken();
        $ids = filter_input(
            INPUT_POST,
            'realblog_ids',
            FILTER_VALIDATE_INT,
            array(
                'flags' => FILTER_REQUIRE_ARRAY,
                'options' => array('min_range' => 1)
            )
        );
        $res = DB::deleteArticlesWithIds($ids);
        if ($res === count($ids)) {
            $this->redirectToOverview();
        } elseif ($res > 0) {
            $info = XH_message('warning', $this->text['deleteall_warning'], $res, count($ids));
        } else {
            $info = XH_message('fail', $this->text['deleteall_error']);
        }
        $title = $this->text['tooltip_delete_selected'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return string
     */
    public function doChangeStatusAction()
    {
        global $title;

        $this->checkCsrfToken();
        $input = filter_input_array(
            INPUT_POST,
            array(
                'realblog_ids' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY,
                    'options' => array('min_range' => 1)
                ),
                'realblog_status' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array('min_range' => 0, 'max_range' => 2)
                )
            )
        );
        $res = DB::updateStatusOfArticlesWithIds($input['realblog_ids'], $input['realblog_status']);
        if ($res === count($input['realblog_ids'])) {
            $this->redirectToOverview();
        } elseif ($res > 0) {
            $info = XH_message('warning', $this->text['changestatus_warning'], $res, count($input['realblog_ids']));
        } else {
            $info = XH_message('fail', $this->text['changestatus_error']);
        }
        $title = $this->text['tooltip_change_status'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return string|null
     */
    private function getCsrfToken()
    {
        global $_XH_csrfProtection;

        $html = $_XH_csrfProtection->tokenInput();
        if (preg_match('/value="([0-9a-f]+)"/', $html, $matches)) {
            return $matches[1];
        }
    }

    /**
     * @return void
     */
    private function checkCsrfToken()
    {
        global $_XH_csrfProtection;

        $_XH_csrfProtection->check();
    }

    /**
     * @param string $title
     * @param string $message
     * @return string
     */
    private function renderInfo($title, $message)
    {
        $url = XH_hsc("{$this->urlPath}?&realblog&admin=plugin_main&action=plugin_text&realblog_page={$this->page}");
        return <<<HTML
<h1>Realblog &ndash; $title</h1>
$message
<p><a href="$url">{$this->text['blog_back']}</a></p>
HTML;
    }

    /**
     * @return no-return
     */
    private function redirectToOverview()
    {
        $url = CMSIMPLE_URL . "?&realblog&admin=plugin_main&action=plugin_text&realblog_page={$this->page}";
        header("Location: $url", true, 303);
        exit;
    }
}
