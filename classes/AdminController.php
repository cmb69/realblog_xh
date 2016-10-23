<?php

/**
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

class AdminController
{
    /**
     * @return void
     * @global string $admin
     * @global string $action
     * @global string $o
     */
    public function dispatch()
    {
        global $admin, $action, $o;

        $this->useCalendar();

        $o .= print_plugin_admin('on');
        switch ($admin) {
            case '':
                $o .= $this->renderInfoView();
                break;
            case 'plugin_main':
                $this->handleMainAdministration();
                break;
            default:
                $o .= plugin_admin_common($action, $admin, 'realblog');
        }
    }

    /**
     * @return string
     */
    private function renderInfoView()
    {
        global $pth;

        $view = new View('info');
        $view->logoPath = "{$pth['folder']['plugins']}realblog/realblog.png";
        $view->version = REALBLOG_VERSION;
        return $view->render();
    }

    /**
     * @return void
     * @global string $action
     */
    private function handleMainAdministration()
    {
        global $action;

        $this->dispatchOnAction($action);
    }

    /**
     * @param string $action
     * @return void
     * @global string $o
     */
    private function dispatchOnAction($action)
    {
        global $o;

        switch ($action) {
            case 'add_realblog':
            case 'modify_realblog':
            case 'delete_realblog':
                $o .= $this->renderArticle();
                break;
            case 'do_add':
                $o .= $this->addArticle();
                break;
            case 'do_modify':
                $o .= $this->modifyArticle();
                break;
            case 'do_delete':
                $o .= $this->deleteArticle();
                break;
            case 'batchdelete':
                $o .= $this->renderConfirmation('delete');
                break;
            case 'do_delselected':
                $o .= $this->deleteArticles();
                break;
            case 'change_status':
                $o .= $this->renderConfirmation('change-status');
                break;
            case 'do_batchchangestatus':
                $o .= $this->changeStatus();
                break;
            default:
                $o .= $this->renderArticles();
        }
    }

    /**
     * @return string
     * @global array $plugin_cf
     * @global Controller $_Realblog_controller
     */
    private function renderArticles()
    {
        global $plugin_cf, $_Realblog_controller;

        $statuses = $this->getFilterStatuses();
        $total = DB::countArticlesWithStatus($statuses);
        $limit = $plugin_cf['realblog']['admin_records_page'];
        $pageCount = ceil($total / $limit);
        $page = max(min($_Realblog_controller->getPage(), $pageCount), 1);
        $offset = ($page - 1) * $limit;
        $articles = DB::findArticlesWithStatus($statuses, $limit, $offset);
        return $this->renderArticlesView($articles, $pageCount);
    }

    private function renderArticlesView($articles, $pageCount)
    {
        global $pth, $sn, $_Realblog_controller;

        $view = new View('articles-form');
        $view->imageFolder = "{$pth['folder']['plugins']}realblog/images/";
        $view->page = $page = min(max($_Realblog_controller->getPage(), 0), $pageCount);
        $view->prevPage = max($page - 1, 1);
        $view->nextPage = min($page + 1, $pageCount);
        $view->lastPage = $pageCount;
        $view->articles = $articles;
        $view->actionUrl = $sn;
        $view->deleteUrl = function ($article) use ($page) {
            global $sn;

            return "$sn?&realblog&admin=plugin_main&action=delete_realblog"
                . "&realblog_id={$article->id}&realblog_page=$page";
        };
        $view->modifyUrl = function ($article) use ($page) {
            global $sn;

            return "$sn?&realblog&admin=plugin_main&action=modify_realblog"
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

    /**
     * @return string
     * @global string $o
     * @global Controller $_Realblog_controller
     */
    private function renderArticle()
    {
        global $action, $_Realblog_controller;

        init_editor(array('realblog_headline_field', 'realblog_story_field'));
        return $this->form(
            $_Realblog_controller->getPgParameter('realblog_id'),
            $action
        );
    }

    /**
     * @return string
     * @global string $title
     * @global array $plugin_tx
     * @global \XH_CSRFProtection $_XH_csrfProtection
     */
    private function addArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection;

        $_XH_csrfProtection->check();
        $article = $this->getArticleFromParameters();
        $res = DB::insertArticle($article);
        if ($res === 1) {
            $this->redirectToOverview();
        } else {
            $info = XH_message('fail', $plugin_tx['realblog']['story_added_error']);
        }
        $title = $plugin_tx['realblog']['tooltip_add'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return string
     * @global string $title
     * @global array $plugin_tx
     * @global \XH_CSRFProtection $_XH_csrfProtection
     */
    private function modifyArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection;

        $_XH_csrfProtection->check();
        $article = $this->getArticleFromParameters();
        $res = DB::updateArticle($article);
        if ($res === 1) {
            $this->redirectToOverview();
        } else {
            $info = XH_message('fail', $plugin_tx['realblog']['story_modified_error']);
        }
        $title = $plugin_tx['realblog']['tooltip_modify'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return string
     * @global string $title
     * @global array $plugin_tx
     * @global \XH_CSRFProtection $_XH_csrfProtection
     * @global Controller $_Realblog_controller
     */
    private function deleteArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $article = $this->getArticleFromParameters();
        $res = DB::deleteArticle($article);
        if ($res === 1) {
            $this->redirectToOverview();
        } else {
            $info = XH_message('fail', $plugin_tx['realblog']['story_deleted_error']);
        }
        $title = $plugin_tx['realblog']['tooltip_delete'];
        return $this->renderInfo($title, $info);
    }

    private function renderConfirmation($kind)
    {
        global $sn, $_Realblog_controller, $_XH_csrfProtection;

        $page = $_Realblog_controller->getPage();
        $view = new View("confirm-$kind");
        if ($kind === 'change-status') {
            $view->states = array(
                'label_status', 'readyforpublishing', 'published', 'archived'
            );
        }
        $view->ids = $_Realblog_controller->getPgParameter('realblog_ids');
        $view->action = "$sn?&realblog&admin=plugin_main";
        $view->url = "$sn?&realblog&admin=plugin_main&action=plugin_text&realblog_page=$page";
        $view->csrfTokenInput = new HtmlString($_XH_csrfProtection->tokenInput());
        return $view->render();
    }

    /**
     * @return string
     *
     * @global string $title
     * @global array $plugin_tx
     * @global \XH_CSRFProtection $_XH_csrfProtection
     * @global Controller $_Realblog_controller
     */
    private function changeStatus()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $ids = $_Realblog_controller->getPgParameter('realblog_ids');
        $status = $_Realblog_controller->getPgParameter('new_realblogstatus');
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
            $info = XH_message('warning', $plugin_tx['realblog']['changestatus_warning'], $res, count($ids));
        } else {
            $info = XH_message('fail', $plugin_tx['realblog']['changestatus_error']);
        }
        $title = $plugin_tx['realblog']['tooltip_changestatus'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return string
     *
     * @global string $title
     * @global array $plugin_tx
     * @global \XH_CSRFProtection $_XH_csrfProtection
     * @global Controller $_Realblog_controller
     */
    private function deleteArticles()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
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
            $info = XH_message('warning', $plugin_tx['realblog']['deleteall_warning'], $res, count($ids));
        } else {
            $info = XH_message('fail', $plugin_tx['realblog']['deleteall_error']);
        }
        $title = $plugin_tx['realblog']['tooltip_deleteall'];
        return $this->renderInfo($title, $info);
    }

    /**
     * @return stdClass
     * @global Controller $_Realblog_controller
     */
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

    /**
     * @return array
     * @global Controller $_Realblog_controller
     */
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
    /**
     * @return void
     * @global array $pth
     * @global string $sl
     * @global string $hjs
     * @todo Check files for existance.
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

    /**
     * @param string $id
     * @param string $action
     * @return string
     * @global string $title
     * @global array $plugin_tx
     */
    private function form($id, $action)
    {
        global $title, $plugin_tx;

        if ($action == 'add_realblog') {
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
            $title = $plugin_tx['realblog']['tooltip_add'];
        } else {
            $article = DB::findById($id);
            if (!$article) {
                return XH_message('fail', $plugin_tx['realblog']['message_not_found']);
            }
            if ($action == 'modify_realblog') {
                $title = $plugin_tx['realblog']['tooltip_modify'] . ' [ID: '
                    . $id . ']';
            } elseif ($action == 'delete_realblog') {
                $title = $plugin_tx['realblog']['tooltip_delete'] . ' [ID: '
                    . $id . ']';
            }
        }
        return $this->renderForm($article, $action);
    }

    private function renderForm(stdClass $article, $action)
    {
        global $pth, $sn, $plugin_cf, $title, $bjs, $_XH_csrfProtection;

        $bjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
            . 'realblog/realblog.js"></script>';
        $view = new View('article-form');
        $view->article = $article;
        $view->title = $title;
        $view->actionUrl = "$sn?&realblog&admin=plugin_main";
        $view->action = 'do_' . $this->getVerb($action);
        $view->tokenInput = new HtmlString($_XH_csrfProtection->tokenInput());
        $view->calendarIcon = "{$pth['folder']['plugins']}realblog/images/calendar.png";
        $view->formatDate = function ($time) {
            return date('Y-m-d', $time);
        };
        $view->isAutoPublish = $plugin_cf['realblog']['auto_publish'];
        $view->isAutoArchive = $plugin_cf['realblog']['auto_archive'];
        $view->states = array('readyforpublishing', 'published', 'archived');
        $view->categories = trim($article->categories, ',');
        $view->button = 'btn_' . $this->getVerb($action);
        return $view->render();
    }

    private function getVerb($action)
    {
        switch ($action) {
            case 'add_realblog':
                return 'add';
            case 'modify_realblog':
                return 'modify';
            case 'delete_realblog':
                return 'delete';
        }
    }

    /**
     * @return void
     * @global Controller $_Realblog_controller
     */
    private function redirectToOverview()
    {
        global $_Realblog_controller;

        $page = $_Realblog_controller->getPage();
        $url = CMSIMPLE_URL . "?&realblog&admin=plugin_main&action=plugin_text&realblog_page=$page";
        header("Location: $url", true, 303);
        exit;
    }

    /**
     * @param string $title
     * @param string $message
     * @return string
     * @global array $plugin_tx
     * @global string $sn
     * @global Controller $_Realblog_controller
     */
    private function renderInfo($title, $message)
    {
        global $plugin_tx, $sn, $_Realblog_controller;

        $page = $_Realblog_controller->getPage();
        $url = XH_hsc("$sn?&realblog&admin=plugin_main&action=plugin_text&realblog_page=$page");
        return <<<HTML
<h1>Realblog &ndash; $title</h1>
$message
<p><a href="$url">{$plugin_tx['realblog']['blog_back']}</a></p>
HTML;
    }
}
