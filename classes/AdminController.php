<?php

/**
 * The admin controllers.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Realblog
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

namespace Realblog;

/**
 * The admin controllers.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class AdminController
{
    /**
     * Dispatches on administration requests.
     *
     * @return void
     *
     * @global string The value of the <var>admin</var> GP parameter.
     * @global string The value of the <var>action</var> GP parameter.
     * @global string The (X)HTML to insert into the contents area.
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
     * Renders the plugin info view.
     *
     * @return string (X)HTML.
     */
    protected function renderInfoView()
    {
        $view = new InfoView();
        return $view->render();
    }

    /**
     * Handles the main administration.
     *
     * @return void
     *
     * @global string The value of the <var>action</var> GP parameter.
     */
    protected function handleMainAdministration()
    {
        global $action;

        $this->dispatchOnAction($action);
    }

    /**
     * Dispatches on the <var>action</var>.
     *
     * @param string $action An action.
     *
     * @return void
     *
     * @global string The (X)HTML to insert into the contents area.
     */
    protected function dispatchOnAction($action)
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
     * Renders the articles.
     *
     * @return string (X)HTML.
     *
     * @global array      The configuration of the plugins.
     * @global array      The localization of the plugins.
     * @global Controller The plugin controller.
     */
    protected function renderArticles()
    {
        global $plugin_cf, $plugin_tx, $_Realblog_controller;

        $articles = Article::findArticlesWithStatus(
            $this->getFilterStatuses()
        );
        $page_record_limit = $plugin_cf['realblog']['admin_records_page'];
        $db_total_records = count($articles);
        $pageCount = ceil($db_total_records / $page_record_limit);
        $page = max(min($_Realblog_controller->getPage(), $pageCount), 1);
        $start_index = ($page - 1) * $page_record_limit;

        $view = new ArticlesAdminView(
            $articles, $page_record_limit, $start_index, $pageCount
        );
        return '<h1>Realblog &ndash; '
            . $plugin_tx['realblog']['story_overview'] . '</h1>'
            . $view->render();
    }

    /**
     * Renders an article.
     *
     * @return string (X)HTML.
     *
     * @global string     The value of the <var>action</var> GP parameter.
     * @global Controller The plugin controller.
     */
    protected function renderArticle()
    {
        global $action, $_Realblog_controller;

        init_editor(array('realblog_headline_field', 'realblog_story_field'));
        return $this->form(
            $_Realblog_controller->getPgParameter('realblogID'), $action
        );
    }

    /**
     * Adds an article.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     * @global Controller        The plugin controller.
     */
    protected function addArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $article = $this->getArticleFromParameters();
        $article->insert();
        $title = $plugin_tx['realblog']['tooltip_add'];
        $info = $plugin_tx['realblog']['story_added'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Modifies an article.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     * @global Controller        The plugin controller.
     */
    protected function modifyArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $article = $this->getArticleFromParameters();
        $article->update();
        $title = $plugin_tx['realblog']['tooltip_modify'];
        $info = $plugin_tx['realblog']['story_modified'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Deletes an article.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     * @global Controller        The plugin controller.
     */
    protected function deleteArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $id = $_Realblog_controller->getPgParameter('realblog_id');
        $article = Article::findById($id);
        $article->delete();
        $title = $plugin_tx['realblog']['tooltip_delete'];
        $info = $plugin_tx['realblog']['story_deleted'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Renders the change status confirmation.
     *
     * @return string (X)HTML.
     */
    protected function confirmChangeStatus()
    {
        $view = new ChangeStatusView();
        return $view->render();
    }

    /**
     * Changes the article status.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     * @global Controller        The plugin controller.
     */
    protected function changeStatus()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $ids = $_Realblog_controller->getPgParameter('realblogtopics');
        $status = $_Realblog_controller->getPgParameter('new_realblogstatus');
        if (is_numeric($status) && $status >= 0 && $status <= 2) {
            foreach ($ids as $id) {
                $article = Article::findById($id);
                $article->setStatus($status);
                $article->update();
            }
            $title = $plugin_tx['realblog']['tooltip_changestatus'];
            $info = $plugin_tx['realblog']['changestatus_done'];
            return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
        } else {
            $title = $plugin_tx['realblog']['tooltip_changestatus'];
            $info = $plugin_tx['realblog']['nochangestatus_done'];
            return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
        }
    }

    /**
     * Renders the delete confirmation.
     *
     * @return string (X)HTML.
     */
    protected function confirmDelete()
    {
        $view = new DeleteView();
        return $view->render();
    }

    /**
     * Deletes articles.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     * @global Controller        The plugin controller.
     */
    protected function deleteArticles()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $ids = $_Realblog_controller->getPgParameter('realblogtopics');
        foreach ($ids as $id) {
            $article = Article::findById($id);
            $article->delete();
        }
        $title = $plugin_tx['realblog']['tooltip_deleteall'];
        $info = $plugin_tx['realblog']['deleteall_done'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Returns an article created from G/P parameters.
     *
     * @return Article
     *
     * @global Controller The plugin controller.
     */
    protected function getArticleFromParameters()
    {
        global $_Realblog_controller;

        $article = new Article();
        $article->setId($_Realblog_controller->getPgParameter('realblog_id'));
        $article->setDate(
            $_Realblog_controller->stringToTime(
                $_Realblog_controller->getPgParameter('realblog_date')
            )
        );
        $article->setTitle(
            stsl($_Realblog_controller->getPgParameter('realblog_title'))
        );
        $article->setTeaser(
            stsl($_Realblog_controller->getPgParameter('realblog_headline'))
        );
        $article->setBody(
            stsl($_Realblog_controller->getPgParameter('realblog_story'))
        );
        $startDate = $_Realblog_controller->getPgParameter('realblog_startdate');
        if (isset($startDate)) {
            $article->setPublishingDate(
                $_Realblog_controller->stringToTime($startDate)
            );
        } else {
            $article->setPublishingDate(0);
        }
        $endDate = $_Realblog_controller->getPgParameter('realblog_enddate');
        if (isset($endDate)) {
            $article->setArchivingDate(
                $_Realblog_controller->stringToTime($endDate)
            );
        } else {
            $article->setArchivingDate(2147483647);
        }
        $article->setStatus(
            $_Realblog_controller->getPgParameter('realblog_status')
        );
        $article->setFeedable(
            $_Realblog_controller->getPgParameter('realblog_rssfeed')
        );
        $article->setCommentable(
            $_Realblog_controller->getPgParameter('realblog_comments')
        );
        return $article;
    }

    /**
     * Returns the current filter statuses.
     *
     * @return array
     *
     * @global Controller The plugin controller.
     */
    protected function getFilterStatuses()
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
     * Writes the required references to the head element.
     *
     * @return void
     *
     * @global array  The paths of system files and folders.
     * @global string The current language.
     * @global string The (X)HTML fragment to insert in the head element.
     *
     * @todo Check files for existance.
     */
    protected function useCalendar()
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
     * Renders the article form.
     *
     * @param string $id     An article ID.
     * @param string $action An action.
     *
     * @return string (X)HTML.
     *
     * @global string              The page title.
     * @global array               The localization of the plugins.
     */
    protected function form($id, $action)
    {
        global $title, $plugin_tx;

        if ($action == 'add_realblog') {
            $article = Article::makeFromRecord(
                array(
                    REALBLOG_ID => 0,
                    REALBLOG_DATE => time(),
                    REALBLOG_STARTDATE => time(),
                    REALBLOG_ENDDATE => 2147483647,
                    REALBLOG_STATUS => 0,
                    REALBLOG_FRONTPAGE => '',
                    REALBLOG_TITLE => '',
                    REALBLOG_HEADLINE => '',
                    REALBLOG_STORY => '',
                    REALBLOG_RSSFEED => '',
                    REALBLOG_COMMENTS => ''
                )
            );
            $title = $plugin_tx['realblog']['tooltip_add'];
        } else {
            $article = Article::findById($id);
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
     * Displays a confirmation.
     *
     * @param string $title A title.
     * @param string $info  An info message.
     * @param int    $page  A blog page to return to.
     *
     * @return string (X)HTML.
     *
     * @global array             The localization of the plugins.
     * @global string            The script name.
     */
    protected function dbconfirm($title, $info, $page)
    {
        global $plugin_tx, $sn;

        $t = '<h1>Realblog &ndash; ' . $title . '</h1>';
        $t .= '<form name="confirm" method="post" action="' . $sn . '?&amp;'
            . 'realblog&amp;admin=plugin_main">';
        $t .= '<table width="100%"><tbody>';
        $t .= '<tr><td class="realblog_confirm_info" align="center">'
            . $info . '</td></tr><tr><td>&nbsp;</td></tr>';
        $t .= '<tr><td class="realblog_confirm_button" align="center">'
            // TODO: don't return via JS
            . tag(
                'input type="button" name="cancel" value="'
                . $plugin_tx['realblog']['btn_ok'] . '" onclick=\'location.href="'
                . $sn . '?&amp;realblog&amp;admin=plugin_main'
                . '&amp;action=plugin_text&amp;page=' . $page . '"\''
            )
            . '</td></tr>';
        $t .= '</tbody></table></form>';
        return $t;
    }

}

?>
