<?php

/**
 * The archive views.
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
 * The archive views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArchiveView
{
    /**
     * The articles.
     *
     * @var array
     */
    private $_articles;

    /**
     * The requested year.
     *
     * @var int
     */
    private $_year;

    /**
     * Initializes a new instance.
     *
     * @param array $articles An array of articles.
     *
     * @return void
     *
     * @global Realblog_Controller The plugin controller.
     */
    public function __construct($articles)
    {
        global $_Realblog_controller;

        $this->_articles = $articles;
        $this->_year = $_Realblog_controller->getYear();
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    public function render()
    {
        global $plugin_tx, $_Realblog_controller;

        $t = '';
        if (!$_Realblog_controller->getPgParameter('realblog_search')) {
            $currentYear = date('Y');
            if (!isset($this->_year) || $this->_year <= 0
                || $this->_year >= $currentYear || empty($this->_year)
            ) {
                $this->_year = $currentYear;
                $currentMonth = date('n');
            } else {
                $currentMonth = 12;
            }
            $next = min($this->_year + 1, $currentYear);
            $back = $this->_year - 1;
            $t .= $this->renderPagination($back, $next);
            $generalrealbloglist = $this->selectArticlesInPeriod(
                mktime(0, 0, 0, 1, 1, $this->_year),
                mktime(0, 0, 0, 1, 1, $this->_year + 1)
            );
            $t .= $this->renderMonthlyArticleLists($currentMonth);
            if (count($generalrealbloglist) == 0) {
                $t .= $plugin_tx['realblog']['no_topics'];
            }
        } else {
            if (count($this->_articles) > 0) {
                $t .= $this->renderSearchResults();
            } else {
                $t .= $plugin_tx['realblog']['no_topics'];
            }
        }
        return $t;
    }

    /**
     * Returns the localized month name.
     *
     * @param int $month A month.
     *
     * @return string
     *
     * @global array The localization of the plugins.
     */
    protected function getMonthName($month)
    {
        global $plugin_tx;

        $monthNames = explode(',', $plugin_tx['realblog']['date_months']);
        return $monthNames[$month - 1];
    }

    /**
     * Selects all articles within a certain period.
     *
     * @param int $start A start timestamp.
     * @param int $end   An end timestamp.
     *
     * @return array
     *
     * @global Realblog_Controller The plugin controller.
     */
    protected function selectArticlesInPeriod($start, $end)
    {
        global $_Realblog_controller;

        $db = $_Realblog_controller->connect();
        $whereClause = new AndWhereClause(
            new SimpleWhereClause(
                REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
            ),
            new SimpleWhereClause(
                REALBLOG_DATE, '>=', $start, INTEGER_COMPARISON
            ),
            new SimpleWhereClause(
                REALBLOG_DATE, '<', $end, INTEGER_COMPARISON
            )
        );
        return $db->selectWhere(
            'realblog.txt', $whereClause, -1,
            array(
                new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
            )
        );
    }

    /**
     * Renders the pagination.
     *
     * @param int $back The previous year.
     * @param int $next The next year.
     *
     * @return string (X)HTML.
     *
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderPagination($back, $next)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $url = $_Realblog_controller->url(
            $su, null, array('realblog_year' => $back)
        );
        $t = '<div class="realblog_table_paging">'
            . '<a href="' . XH_hsc($url) . '" title="'
            . $plugin_tx['realblog']['tooltip_previousyear'] . '">'
            . '&#9664;</a>&nbsp;&nbsp;';
        $t .= '<b>' . $plugin_tx['realblog']['archive_year']
            . $this->_year . '</b>';
        $url = $_Realblog_controller->url(
            $su, null, array('realblog_year' => $next)
        );
        $t .= '&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
            . $plugin_tx['realblog']['tooltip_nextyear'] . '">'
            . '&#9654;</a>';
        $t .= '</div>';
        return $t;
    }

    /**
     * Renders the monthly article lists.
     *
     * @param int $currentMonth The current month.
     *
     * @return string (X)HTML.
     */
    protected function renderMonthlyArticleLists($currentMonth)
    {
        $t = '';
        for ($month = $currentMonth; $month >= 1; $month--) {
            $realbloglist = $this->selectArticlesInPeriod(
                mktime(0, 0, 0, $month, 1, $this->_year),
                mktime(0, 0, 0, $month + 1, 1, $this->_year)
            );
            $monthName = $this->getMonthName($month);
            if (count($realbloglist) > 0) {
                $t .= '<h4>' . $monthName . ' ' . $this->_year . '</h4>'
                    . $this->renderArticleList($realbloglist);
            }
        }
        return $t;
    }

    /**
     * Renders an article list.
     *
     * @param array $articles An array of articles.
     *
     * @return string (X)HTML.
     *
     * @global string              The URL of the current page.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderArticleList($articles)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $t = '<ul class="realblog_archive">';
        foreach ($articles as $key => $field) {
            $url = $_Realblog_controller->url(
                $su, $field[REALBLOG_TITLE], array(
                    'realblogID' => $field[REALBLOG_ID]
                )
            );
            $t .= '<li>'
                . date($plugin_tx['realblog']['date_format'], $field[REALBLOG_DATE])
                . '&nbsp;&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">'
                . $field[REALBLOG_TITLE] . '</a></li>';
        }
        $t .= '</ul>';
        return $t;
    }

    /**
     * Renders the search results.
     *
     * @return string (X)HTML.
     *
     * @global string              The URL of the current page.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderSearchResults()
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $currentMonth = -1;
        $t = '';
        foreach ($this->_articles as $key => $field) {
            $month = date('n', $field[REALBLOG_DATE]);
            $year = date('Y', $field[REALBLOG_DATE]);
            if ($month != $currentMonth) {
                $t .= '<h4>' . $this->getMonthName($month) . ' ' . $year . '</h4>';
                $currentMonth = $month;
            }
            $url = $_Realblog_controller->url(
                $su, $field[REALBLOG_TITLE], array(
                    'realblogID' => $field[REALBLOG_ID]
                )
            );
            $t .= '<p>'
                . date($plugin_tx['realblog']['date_format'], $field[REALBLOG_DATE])
                . '&nbsp;&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">'
                . $field[REALBLOG_TITLE] . '</a></p>';
        }
        return $t;
    }
}

?>
