<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

class MainAdminController extends AbstractController
{
    private $urlPath;

    private $page;

    public function __construct()
    {
        global $sn, $_Realblog_controller;

        parent::__construct();
        $this->urlPath = $sn;
        $this->page = $_Realblog_controller->getPage();
    }

    public function defaultAction()
    {
        $statuses = $this->getFilterStatuses();
        $total = DB::countArticlesWithStatus($statuses);
        $limit = $this->config['admin_records_page'];
        $pageCount = ceil($total / $limit);
        $page = max(min($this->page, $pageCount), 1);
        $offset = ($page - 1) * $limit;
        $articles = DB::findArticlesWithStatus($statuses, $limit, $offset);
        return $this->renderArticles($articles, $pageCount);
    }

    private function getFilterStatuses()
    {
        global $_Realblog_controller;

        $statuses = array();
        for ($i = 0; $i <= 2; $i++) {
            if ($_Realblog_controller->getFilter($i)) {
                $statuses[] = $i;
            }
        }
        return $statuses;
    }

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
        $view->deleteUrl = function ($article) use ($page) {
            global $sn;

            return "$sn?&realblog&admin=plugin_main&action=delete"
                . "&realblog_id={$article->id}&realblog_page=$page";
        };
        $view->editUrl = function ($article) use ($page) {
            global $sn;

            return "$sn?&realblog&admin=plugin_main&action=edit"
                . "&realblog_id={$article->id}&realblog_page=$page";
        };
        $view->states = array('readyforpublishing', 'published', 'archived');
        $view->hasFilter = function ($num) {
            global $_Realblog_controller;

            return $_Realblog_controller->getFilter($num);
        };
        $view->formatDate = function ($article) {
            global $plugin_tx;

            return date($plugin_tx['realblog']['date_format'], $article->date);
        };
        return $view->render();
    }

    public function createAction()
    {
        return $this->renderArticle('create');
    }

    public function editAction()
    {
        return $this->renderArticle('edit');
    }

    public function deleteAction()
    {
        return $this->renderArticle('delete');
    }

    private function renderArticle($action)
    {
        global $_Realblog_controller;

        init_editor(array('realblog_headline_field', 'realblog_story_field'));
        return $this->form(
            $_Realblog_controller->getPgParameter('realblog_id'),
            $action
        );
    }

    private function form($id, $action)
    {
        global $title;

        if ($action == 'create') {
            $article = (object) array(
                'id' => null,
                'version' => 0,
                'date' => time(),
                'publishing_date' => time(),
                'archiving_date' => 2147483647,
                'status' => 0,
                'categories' => '',
                'title' => '',
                'teaser' => '',
                'body' => '',
                'feedable' => 0,
                'commentable' => 0
            );
            $title = $this->text['tooltip_create'];
        } else {
            $article = DB::findById($id);
            if (!$article) {
                return XH_message('fail', $this->text['message_not_found']);
            }
            if ($action == 'edit') {
                $title = "{$this->text['tooltip_edit']} #$id";
            } elseif ($action == 'delete') {
                $title = "{$this->text['tooltip_delete']} #$id";
            }
        }
        return $this->renderForm($article, $action);
    }

    private function renderForm(stdClass $article, $action)
    {
        global $pth, $sn, $plugin_cf, $title, $bjs;

        $this->useCalendar();
        $bjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
            . 'realblog/realblog.js"></script>';
        $view = new View('article-form');
        $view->article = $article;
        $view->title = $title;
        $view->actionUrl = "$sn?&realblog&admin=plugin_main";
        $view->action = "do_{$action}";
        $view->csrfToken = $this->getCsrfToken();
        $view->calendarIcon = "{$pth['folder']['plugins']}realblog/images/calendar.png";
        $view->formatDate = function ($time) {
            return date('Y-m-d', $time);
        };
        $view->isAutoPublish = $plugin_cf['realblog']['auto_publish'];
        $view->isAutoArchive = $plugin_cf['realblog']['auto_archive'];
        $view->states = array('readyforpublishing', 'published', 'archived');
        $view->categories = trim($article->categories, ',');
        $view->button = "btn_{$action}";
        return $view->render();
    }

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
<script type="text/javascript">/* <![CDATA[ */
var REALBLOG = REALBLOG || {};
(function () {
    var input = document.createElement("input");
    input.setAttribute("type", "date");
    REALBLOG.hasNativeDatePicker = (input.type == "date");
    if (!REALBLOG.hasNativeDatePicker) {
        document.write(
            '<link rel="stylesheet" type="text/css" href="$stylesheet">' +
            '<script type="text/javascript" src="$mainScript"><\/script>' +
            '<script type="text/javascript" src="$languageScript"><\/script>' +
            '<script type="text/javascript" src="$setupScript"><\/script>'
        );
    }
}());
/* ]]> */</script>
EOT;
    }

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

    private function getArticleFromParameters()
    {
        global $_Realblog_controller;

        $article = new stdClass();
        $article->id = stsl($_POST['realblog_id']);
        $article->version = stsl($_POST['realblog_version']);
        $article->date = $_Realblog_controller->stringToTime(stsl($_POST['realblog_date']));
        $article->title = stsl($_POST['realblog_title']);
        $article->teaser = stsl($_POST['realblog_headline']);
        $article->body = stsl($_POST['realblog_story']);
        $article->publishing_date = stsl($_POST['realblog_startdate']);
        $article->archiving_date = stsl($_POST['realblog_enddate']);
        $article->status = stsl($_POST['realblog_status']);
        $article->feedable = (bool) stsl($_POST['realblog_rssfeed']);
        $article->commentable = (bool) stsl($_POST['realblog_comments']);
        $article->categories = ',' . trim(stsl($_POST['realblog_categories'])) . ',';
        return $article;
    }

    public function deleteSelectedAction()
    {
        return $this->renderConfirmation('delete');
    }

    public function changeStatusAction()
    {
        return $this->renderConfirmation('change-status');
    }

    private function renderConfirmation($kind)
    {
        global $_Realblog_controller;

        $view = new View("confirm-$kind");
        if ($kind === 'change-status') {
            $view->states = array(
                'new_realblogstatus', 'readyforpublishing', 'published', 'archived'
            );
        }
        $view->ids = $_Realblog_controller->getPgParameter('realblog_ids');
        $view->action = "{$this->urlPath}?&realblog&admin=plugin_main";
        $view->url = "{$this->urlPath}?&realblog&admin=plugin_main&action=plugin_text&realblog_page={$this->page}";
        $view->csrfToken = $this->getCsrfToken();
        return $view->render();
    }

    public function doDeleteSelectedAction()
    {
        global $title, $_Realblog_controller;

        $this->checkCsrfToken();
        $ids = $_Realblog_controller->getPgParameter('realblog_ids');
        $ids = array_map(
            function ($id) {
                return (int) $id;
            },
            $ids
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

    public function doChangeStatusAction()
    {
        global $title, $_Realblog_controller;

        $this->checkCsrfToken();
        $ids = $_Realblog_controller->getPgParameter('realblog_ids');
        $status = $_Realblog_controller->getPgParameter('realblog_status');
        $ids = array_map(
            function ($id) {
                return (int) $id;
            },
            $ids
        );
        $res = DB::updateStatusOfArticlesWithIds($ids, $status);
        if ($res === count($ids)) {
            $this->redirectToOverview();
        } elseif ($res > 0) {
            $info = XH_message('warning', $this->text['changestatus_warning'], $res, count($ids));
        } else {
            $info = XH_message('fail', $this->text['changestatus_error']);
        }
        $title = $this->text['tooltip_change_status'];
        return $this->renderInfo($title, $info);
    }

    private function getCsrfToken()
    {
        global $_XH_csrfProtection;

        $html = $_XH_csrfProtection->tokenInput();
        if (preg_match('/value="([0-9a-f]+)"/', $html, $matches)) {
            return $matches[1];
        }
    }

    private function checkCsrfToken()
    {
        global $_XH_csrfProtection;

        $_XH_csrfProtection->check();
    }

    private function renderInfo($title, $message)
    {
        $url = XH_hsc("{$this->urlPath}?&realblog&admin=plugin_main&action=plugin_text&realblog_page={$this->page}");
        return <<<HTML
<h1>Realblog &ndash; $title</h1>
$message
<p><a href="$url">{$this->text['blog_back']}</a></p>
HTML;
    }

    private function redirectToOverview()
    {
        $url = CMSIMPLE_URL . "?&realblog&admin=plugin_main&action=plugin_text&realblog_page={$this->page}";
        header("Location: $url", true, 303);
        exit;
    }
}
