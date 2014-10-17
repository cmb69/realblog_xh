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
     */
    public function __construct()
    {
        $this->_db = Realblog_connect();
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

        Realblog_useCalendar();

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
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    private function _renderArticles()
    {
        global $plugin_cf, $plugin_tx;

        $records = $this->_db->selectWhere(
            'realblog.txt', $this->_getFilterClause(), -1,
            new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
        );

        $page_record_limit = $plugin_cf['realblog']['admin_records_page'];
        $db_total_records = count($records);
        $pageCount = ceil($db_total_records / $page_record_limit);
        $page = max(min(Realblog_getPage(), $pageCount), 1);
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
     * @global string The value of the <var>action</var> GP parameter.
     */
    private function _renderArticle()
    {
        global $action;

        init_editor(array('realblog_headline_field', 'realblog_story_field'));
        return Realblog_form(Realblog_getPgParameter('realblogID'), $action);
    }

    /**
     * Adds an article.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _addArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection;

        $_XH_csrfProtection->check();
        $article = $this->_getArticleFromParameters();
        $this->_db->insertWithAutoId('realblog.txt', REALBLOG_ID, $article);
        $title = $plugin_tx['realblog']['tooltip_add'];
        $info = $plugin_tx['realblog']['story_added'];
        return Realblog_dbconfirm($title, $info, Realblog_getPage());
    }

    /**
     * Modifies an article.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _modifyArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection;

        $_XH_csrfProtection->check();
        $article = $this->_getArticleFromParameters();
        $this->_db->updateRowById('realblog.txt', REALBLOG_ID, $article);
        $title = $plugin_tx['realblog']['tooltip_modify'];
        $info = $plugin_tx['realblog']['story_modified'];
        return Realblog_dbconfirm($title, $info, Realblog_getPage());
    }

    /**
     * Deletes an article.
     *
     * @return string (X)HTML.
     *
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _deleteArticle()
    {
        global $title, $plugin_tx, $_XH_csrfProtection;

        $_XH_csrfProtection->check();
        $id = Realblog_getPgParameter('realblog_id');
        $this->_db->deleteWhere(
            'realblog.txt', new SimpleWhereClause(REALBLOG_ID, '=', $id),
            INTEGER_COMPARISON
        );
        $title = $plugin_tx['realblog']['tooltip_delete'];
        $info = $plugin_tx['realblog']['story_deleted'];
        return Realblog_dbconfirm($title, $info, Realblog_getPage());
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
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _changeStatus()
    {
        global $title, $plugin_tx, $_XH_csrfProtection;

        $_XH_csrfProtection->check();
        $ids = Realblog_getPgParameter('realblogtopics');
        $status = Realblog_getPgParameter('new_realblogstatus');
        if (is_numeric($status) && $status >= 0 && $status <= 2) {
            foreach ($ids as $id) {
                $article = array();
                $article[REALBLOG_ID] = $id;
                $article[REALBLOG_STATUS] = $status;
                $this->_db->updateRowById('realblog.txt', REALBLOG_ID, $article);
            }
            $title = $plugin_tx['realblog']['tooltip_changestatus'];
            $info = $plugin_tx['realblog']['changestatus_done'];
            return Realblog_dbconfirm($title, $info, Realblog_getPage());
        } else {
            $title = $plugin_tx['realblog']['tooltip_changestatus'];
            $info = $plugin_tx['realblog']['nochangestatus_done'];
            return Realblog_dbconfirm($title, $info, Realblog_getPage());
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
     * @global string            The page title.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _deleteArticles()
    {
        global $title, $plugin_tx, $_XH_csrfProtection;

        $_XH_csrfProtection->check();
        $ids = Realblog_getPgParameter('realblogtopics');
        foreach ($ids as $id) {
            $this->_db->deleteWhere(
                'realblog.txt', new SimpleWhereClause(REALBLOG_ID, '=', $id),
                INTEGER_COMPARISON
            );
        }
        $title = $plugin_tx['realblog']['tooltip_deleteall'];
        $info = $plugin_tx['realblog']['deleteall_done'];
        return Realblog_dbconfirm($title, $info, Realblog_getPage());
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
     */
    private function _getArticleFromParameters()
    {
        $article = array();
        $article[REALBLOG_ID] = Realblog_getPgParameter('realblog_id');
        $article[REALBLOG_DATE] = Realblog_stringToTime(
            Realblog_getPgParameter('realblog_date')
        );
        $article[REALBLOG_TITLE] = stsl(
            Realblog_getPgParameter('realblog_title')
        );
        $article[REALBLOG_HEADLINE] = stsl(
            Realblog_getPgParameter('realblog_headline')
        );
        $article[REALBLOG_STORY] = stsl(
            Realblog_getPgParameter('realblog_story')
        );
        $article[REALBLOG_FRONTPAGE] = Realblog_getPgParameter(
            'realblog_frontpage'
        );
        $startDate = Realblog_getPgParameter('realblog_startdate');
        if (isset($startDate)) {
            $article[REALBLOG_STARTDATE] = Realblog_stringToTime($startDate);
        } else {
            $article[REALBLOG_STARTDATE] = 0;
        }
        $endDate = Realblog_getPgParameter('realblog_enddate');
        if (isset($endDate)) {
            $article[REALBLOG_ENDDATE] = Realblog_stringToTime($endDate);
        } else {
            $article[REALBLOG_ENDDATE] = 2147483647;
        }
        $article[REALBLOG_STATUS] = Realblog_getPgParameter('realblog_status');
        $article[REALBLOG_RSSFEED] = Realblog_getPgParameter('realblog_rssfeed');
        $article[REALBLOG_COMMENTS] = Realblog_getPgParameter('realblog_comments');
        return $article;
    }

    /**
     * Returns the current filter clause.
     *
     * @return WhereClause
     */
    private function _getFilterClause()
    {
        $filterClause = null;
        foreach (range(0, 2) as $i) {
            if (Realblog_getFilter($i + 1)) {
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
}

?>
