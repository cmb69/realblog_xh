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
        $view = new InfoView();
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
                $o .= $this->confirmDelete();
                break;
            case 'do_delselected':
                $o .= $this->deleteArticles();
                break;
            case 'change_status':
                $o .= $this->confirmChangeStatus();
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
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderArticles()
    {
        global $plugin_cf, $plugin_tx, $_Realblog_controller;

        $statuses = $this->getFilterStatuses();
        $total = DB::countArticlesWithStatus($statuses);
        $limit = $plugin_cf['realblog']['admin_records_page'];
        $pageCount = ceil($total / $limit);
        $page = max(min($_Realblog_controller->getPage(), $pageCount), 1);
        $offset = ($page - 1) * $limit;
        $articles = DB::findArticlesWithStatus($statuses, $limit, $offset);
        $view = new ArticlesAdminView($articles, $total, $pageCount);
        return '<h1>Realblog &ndash; '
            . $plugin_tx['realblog']['story_overview'] . '</h1>'
            . $view->render();
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
            $_Realblog_controller->getPgParameter('realblogID'),
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
        DB::insertArticle($article);
        $title = $plugin_tx['realblog']['tooltip_add'];
        $info = $plugin_tx['realblog']['story_added'];
        return $this->dbconfirm($title, $info);
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
        DB::updateArticle($article);
        $title = $plugin_tx['realblog']['tooltip_modify'];
        $info = $plugin_tx['realblog']['story_modified'];
        return $this->dbconfirm($title, $info);
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
        $id = $_Realblog_controller->getPgParameter('realblog_id');
        DB::deleteArticleWithId($id);
        $title = $plugin_tx['realblog']['tooltip_delete'];
        $info = $plugin_tx['realblog']['story_deleted'];
        return $this->dbconfirm($title, $info);
    }

    /**
     * @return string
     */
    private function confirmChangeStatus()
    {
        $view = new ChangeStatusView();
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
        $ids = $_Realblog_controller->getPgParameter('realblogtopics');
        $status = $_Realblog_controller->getPgParameter('new_realblogstatus');
        if (is_numeric($status) && $status >= 0 && $status <= 2) {
            $ids = array_map(
                function ($id) {
                    return (int) $id;
                },
                $ids
            );
            DB::updateStatusOfArticlesWithIds($ids, $status);
            $title = $plugin_tx['realblog']['tooltip_changestatus'];
            $info = $plugin_tx['realblog']['changestatus_done'];
            return $this->dbconfirm($title, $info);
        } else {
            $title = $plugin_tx['realblog']['tooltip_changestatus'];
            $info = $plugin_tx['realblog']['nochangestatus_done'];
            return $this->dbconfirm($title, $info);
        }
    }

    /**
     * @return string
     */
    private function confirmDelete()
    {
        $view = new DeleteView();
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
    private function deleteArticles()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $ids = $_Realblog_controller->getPgParameter('realblogtopics');
        $ids = array_map(
            function ($id) {
                return (int) $id;
            },
            $ids
        );
        DB::deleteArticlesWithIds($ids);
        $title = $plugin_tx['realblog']['tooltip_deleteall'];
        $info = $plugin_tx['realblog']['deleteall_done'];
        return $this->dbconfirm($title, $info);
    }

    /**
     * @return stdClass
     * @global Controller $_Realblog_controller
     */
    private function getArticleFromParameters()
    {
        global $_Realblog_controller;

        $article = new stdClass();
        $article->id = $_Realblog_controller->getPgParameter('realblog_id');
        $article->date =
            $_Realblog_controller->stringToTime(
                $_Realblog_controller->getPgParameter('realblog_date')
            );
        $article->title =
            stsl($_Realblog_controller->getPgParameter('realblog_title'));
        $article->teaser =
            stsl($_Realblog_controller->getPgParameter('realblog_headline'));
        $article->body =
            stsl($_Realblog_controller->getPgParameter('realblog_story'));
        $startDate = $_Realblog_controller->getPgParameter('realblog_startdate');
        if (isset($startDate)) {
            $article->publishing_date =
                $_Realblog_controller->stringToTime($startDate);
        } else {
            $article->publishing_date = 0;
        }
        $endDate = $_Realblog_controller->getPgParameter('realblog_enddate');
        if (isset($endDate)) {
            $article->archiving_date =
                $_Realblog_controller->stringToTime($endDate);
        } else {
            $article->archiving_date = 2147483647;
        }
        $article->status =
            $_Realblog_controller->getPgParameter('realblog_status');
        $article->feedable = (bool)
            $_Realblog_controller->getPgParameter('realblog_rssfeed');
        $article->commentable = (bool)
            $_Realblog_controller->getPgParameter('realblog_comments');
        $article->categories = ','
            . trim($_Realblog_controller->getPgParameter('realblog_categories'))
            . ',';
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
            if ($_Realblog_controller->getFilter($i + 1)) {
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
            if ($action == 'modify_realblog') {
                $title = $plugin_tx['realblog']['tooltip_modify'] . ' [ID: '
                    . $id . ']';
            } elseif ($action == 'delete_realblog') {
                $title = $plugin_tx['realblog']['tooltip_delete'] . ' [ID: '
                    . $id . ']';
            }
        }
        $view = new ArticleAdminView($article, $action);
        return $view->render();
    }

    /**
     * @param string $title
     * @param string $info
     * @return string
     * @global array $plugin_tx
     * @global string $sn
     * @global Controller $_Realblog_controller
     */
    private function dbconfirm($title, $info)
    {
        global $plugin_tx, $sn, $_Realblog_controller;

        $message = XH_message('success', $info);
        $page = $_Realblog_controller->getPage();
        $url = XH_hsc("$sn?&realblog&admin=plugin_main&action=plugin_text&page=$page");
        return <<<HTML
<h1>Realblog &ndash; $title</h1>
$message
<p><a href="$url">{$plugin_tx['realblog']['blog_back']}</a></p>
HTML;
    }
}
