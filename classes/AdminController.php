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
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The admin controllers.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_AdminController
{
    /**
     * The database connection.
     *
     * @var Flatfile
     */
    private $_db;

    /**
     * Initializes a new instance.
     *
     * @return void
     *
     * @global Realblog_Controller The plugin controller.
     */
    public function __construct()
    {
        global $_Realblog_controller;

        $this->_db = $_Realblog_controller->connect();
    }

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
            $o .= $this->_renderInfoView();
            break;
        case 'plugin_main':
            $this->_handleMainAdministration();
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
    private function _renderInfoView()
    {
        $view = new Realblog_InfoView();
        return $view->render();
    }

    /**
     * Handles the main administration.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     * @global string The (X)HTML to insert into the contents area.
     * @global string The value of the <var>action</var> GP parameter.
     */
    private function _handleMainAdministration()
    {
        global $pth, $o, $action;

        $filename = $pth['folder']['content'] . 'realblog/realblog.txt';
        if (!file_exists($filename)) {
            mkdir($pth['folder']['content'] . 'realblog');
            file_put_contents($filename, '');
            chmod($filename, 0644);
            file_put_contents($filename . '.lock', '');
            chmod($filename . '.lock', 0644);
        }
        if (!is_writable($filename)) {
            $o .= $this->_renderDatafileError();
        } else {
            $this->_dispatchOnAction($action);
        }
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
    private function _dispatchOnAction($action)
    {
        global $o;

        switch ($action) {
        case 'add_realblog':
        case 'modify_realblog':
        case 'delete_realblog':
            $o .= $this->_renderArticle();
            break;
        case 'do_add':
            $o .= $this->_addArticle();
            break;
        case 'do_modify':
            $o .= $this->_modifyArticle();
            break;
        case 'do_delete':
            $o .= $this->_deleteArticle();
            break;
        case 'batchdelete':
            $o .= $this->_confirmDelete();
            break;
        case 'do_delselected':
            $o .= $this->_deleteArticles();
            break;
        case 'change_status':
            $o .= $this->_confirmChangeStatus();
            break;
        case 'do_batchchangestatus':
            $o .= $this->_changeStatus();
            break;
        default:
            $o .= $this->_renderArticles();
        }
    }

    /**
     * Renders the articles.
     *
     * @return string (X)HTML.
     *
     * @global array               The configuration of the plugins.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    private function _renderArticles()
    {
        global $plugin_cf, $plugin_tx, $_Realblog_controller;

        $records = $this->_db->selectWhere(
            'realblog.txt', $this->_getFilterClause(), -1,
            new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
        );

        $page_record_limit = $plugin_cf['realblog']['admin_records_page'];
        $db_total_records = count($records);
        $pageCount = ceil($db_total_records / $page_record_limit);
        $page = max(min($_Realblog_controller->getPage(), $pageCount), 1);
        $start_index = ($page - 1) * $page_record_limit;

        $view = new Realblog_ArticlesAdminView(
            $records, $page_record_limit, $start_index, $pageCount
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
     * @global string              The value of the <var>action</var> GP parameter.
     * @global Realblog_Controller The plugin controller.
     */
    private function _renderArticle()
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
     * @global string              The page title.
     * @global array               The localization of the plugins.
     * @global XH_CSRFProtection   The CSRF protector.
     * @global Realblog_Controller The plugin controller.
     */
    private function _addArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_Controller;

        $_XH_csrfProtection->check();
        $article = $this->_getArticleFromParameters();
        $this->_db->insertWithAutoId('realblog.txt', REALBLOG_ID, $article);
        $title = $plugin_tx['realblog']['tooltip_add'];
        $info = $plugin_tx['realblog']['story_added'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Modifies an article.
     *
     * @return string (X)HTML.
     *
     * @global string              The page title.
     * @global array               The localization of the plugins.
     * @global XH_CSRFProtection   The CSRF protector.
     * @global Realblog_Controller The plugin controller.
     */
    private function _modifyArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $article = $this->_getArticleFromParameters();
        $this->_db->updateRowById('realblog.txt', REALBLOG_ID, $article);
        $title = $plugin_tx['realblog']['tooltip_modify'];
        $info = $plugin_tx['realblog']['story_modified'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Deletes an article.
     *
     * @return string (X)HTML.
     *
     * @global string              The page title.
     * @global array               The localization of the plugins.
     * @global XH_CSRFProtection   The CSRF protector.
     * @global Realblog_Controller The plugin controller.
     */
    private function _deleteArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $id = $_Realblog_controller->getPgParameter('realblog_id');
        $this->_db->deleteWhere(
            'realblog.txt', new SimpleWhereClause(REALBLOG_ID, '=', $id),
            INTEGER_COMPARISON
        );
        $title = $plugin_tx['realblog']['tooltip_delete'];
        $info = $plugin_tx['realblog']['story_deleted'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Renders the change status confirmation.
     *
     * @return string (X)HTML.
     */
    private function _confirmChangeStatus()
    {
        $view = new Realblog_ChangeStatusView();
        return $view->render();
    }

    /**
     * Changes the article status.
     *
     * @return string (X)HTML.
     *
     * @global string              The page title.
     * @global array               The localization of the plugins.
     * @global XH_CSRFProtection   The CSRF protector.
     * @global Realblog_Controller The plugin controller.
     */
    private function _changeStatus()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $ids = $_Realblog_controller->getPgParameter('realblogtopics');
        $status = $_Realblog_controller->getPgParameter('new_realblogstatus');
        if (is_numeric($status) && $status >= 0 && $status <= 2) {
            foreach ($ids as $id) {
                $article = array();
                $article[REALBLOG_ID] = $id;
                $article[REALBLOG_STATUS] = $status;
                $this->_db->updateRowById('realblog.txt', REALBLOG_ID, $article);
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
    private function _confirmDelete()
    {
        $view = new Realblog_DeleteView();
        return $view->render();
    }

    /**
     * Deletes articles.
     *
     * @return string (X)HTML.
     *
     * @global string              The page title.
     * @global array               The localization of the plugins.
     * @global XH_CSRFProtection   The CSRF protector.
     * @global Realblog_Controller The plugin controller.
     */
    private function _deleteArticles()
    {
        global $title, $plugin_tx, $_XH_csrfProtection, $_Realblog_controller;

        $_XH_csrfProtection->check();
        $ids = $_Realblog_controller->getPgParameter('realblogtopics');
        foreach ($ids as $id) {
            $this->_db->deleteWhere(
                'realblog.txt', new SimpleWhereClause(REALBLOG_ID, '=', $id),
                INTEGER_COMPARISON
            );
        }
        $title = $plugin_tx['realblog']['tooltip_deleteall'];
        $info = $plugin_tx['realblog']['deleteall_done'];
        return $this->dbconfirm($title, $info, $_Realblog_controller->getPage());
    }

    /**
     * Renders the data file error.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderDatafileError()
    {
        global $plugin_tx;

        return '<h1>Realblog</h1>'
            . XH_message('fail', $plugin_tx['realblog']['message_datafile']);
    }

    /**
     * Returns an article record created from G/P parameters.
     *
     * @return array
     *
     * @global Realblog_Controller The plugin controller.
     */
    private function _getArticleFromParameters()
    {
        global $_Realblog_controller;

        $article = array();
        $article[REALBLOG_ID] = $_Realblog_controller->getPgParameter('realblog_id');
        $article[REALBLOG_DATE] = $_Realblog_controller->stringToTime(
            $_Realblog_controller->getPgParameter('realblog_date')
        );
        $article[REALBLOG_TITLE] = stsl(
            $_Realblog_controller->getPgParameter('realblog_title')
        );
        $article[REALBLOG_HEADLINE] = stsl(
            $_Realblog_controller->getPgParameter('realblog_headline')
        );
        $article[REALBLOG_STORY] = stsl(
            $_Realblog_controller->getPgParameter('realblog_story')
        );
        $article[REALBLOG_FRONTPAGE] = $_Realblog_controller->getPgParameter(
            'realblog_frontpage'
        );
        $startDate = $_Realblog_controller->getPgParameter('realblog_startdate');
        if (isset($startDate)) {
            $article[REALBLOG_STARTDATE]
                = $_Realblog_controller->stringToTime($startDate);
        } else {
            $article[REALBLOG_STARTDATE] = 0;
        }
        $endDate = $_Realblog_controller->getPgParameter('realblog_enddate');
        if (isset($endDate)) {
            $article[REALBLOG_ENDDATE]
                = $_Realblog_controller->stringToTime($endDate);
        } else {
            $article[REALBLOG_ENDDATE] = 2147483647;
        }
        $article[REALBLOG_STATUS]
            = $_Realblog_controller->getPgParameter('realblog_status');
        $article[REALBLOG_RSSFEED]
            = $_Realblog_controller->getPgParameter('realblog_rssfeed');
        $article[REALBLOG_COMMENTS]
            = $_Realblog_controller->getPgParameter('realblog_comments');
        return $article;
    }

    /**
     * Returns the current filter clause.
     *
     * @return WhereClause
     *
     * @global Realblog_Controller The plugin controller.
     */
    private function _getFilterClause()
    {
        global $_Realblog_controller;

        $filterClause = null;
        foreach (range(0, 2) as $i) {
            if ($_Realblog_controller->getFilter($i + 1)) {
                if (isset($filterClause)) {
                    $filterClause = new OrWhereClause(
                        $filterClause,
                        new SimpleWhereClause(REALBLOG_STATUS, "=", $i)
                    );
                } else {
                    $filterClause = new SimpleWhereClause(
                        REALBLOG_STATUS, "=", $i
                    );
                }
            }
        }
        return $filterClause;
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
     * @global array               The configuration of the plugins.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function form($id, $action)
    {
        global $title, $plugin_cf, $plugin_tx, $_Realblog_controller;

        $db = $_Realblog_controller->connect();
        if ($action == 'add_realblog') {
            $record = array(
                REALBLOG_ID => 0,
                REALBLOG_DATE => date('Y-m-d'),
                REALBLOG_STARTDATE => date('Y-m-d'),
                REALBLOG_ENDDATE => date('Y-m-d', 2147483647),
                REALBLOG_STATUS => 0,
                REALBLOG_FRONTPAGE => '',
                REALBLOG_TITLE => '',
                REALBLOG_HEADLINE => '',
                REALBLOG_STORY => '',
                REALBLOG_RSSFEED => '',
                REALBLOG_COMMENTS => ''
            );
            $title = $plugin_tx['realblog']['tooltip_add'];
        } else {
            $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $id);
            $realblog_id = $record[REALBLOG_ID];
            $record[REALBLOG_DATE] = date('Y-m-d', $record[REALBLOG_DATE]);
            $record[REALBLOG_STARTDATE] = date(
                'Y-m-d', (int) $record[REALBLOG_STARTDATE]
            );
            $record[REALBLOG_ENDDATE] = date('Y-m-d', $record[REALBLOG_ENDDATE]);
            if ($action == 'modify_realblog') {
                $title = $plugin_tx['realblog']['tooltip_modify'] . ' [ID: '
                    . $id . ']';
            } elseif ($action == 'delete_realblog') {
                $title = $plugin_tx['realblog']['tooltip_delete'] . ' [ID: '
                    . $id . ']';
            }
        }
        $view = new Realblog_ArticleAdminView($record, $action);
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
