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
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
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
    protected $articles;

    /**
     * The requested year.
     *
     * @var int
     */
    protected $year;

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

        $this->articles = $articles;
        $this->year = $_Realblog_controller->getYear();
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
            if (!isset($this->year) || $this->year <= 0
                || $this->year >= $currentYear || empty($this->year)
            ) {
                $this->year = $currentYear;
                $currentMonth = date('n');
            } else {
                $currentMonth = 12;
            }
            $next = min($this->year + 1, $currentYear);
            $back = $this->year - 1;
            $t .= $this->renderPagination($back, $next);
            $generalrealbloglist = Realblog_Article::findArchivedArticlesInPeriod(
                mktime(0, 0, 0, 1, 1, $this->year),
                mktime(0, 0, 0, 1, 1, $this->year + 1)
            );
            $t .= $this->renderMonthlyArticleLists($currentMonth);
            if (count($generalrealbloglist) == 0) {
                $t .= $plugin_tx['realblog']['no_topics'];
            }
        } else {
            if (count($this->articles) > 0) {
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
            . $this->year . '</b>';
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
            $realbloglist = Realblog_Article::findArchivedArticlesInPeriod(
                mktime(0, 0, 0, $month, 1, $this->year),
                mktime(0, 0, 0, $month + 1, 1, $this->year)
            );
            $monthName = $this->getMonthName($month);
            if (count($realbloglist) > 0) {
                $t .= '<h4>' . $monthName . ' ' . $this->year . '</h4>'
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
        foreach ($articles as $article) {
            $url = $_Realblog_controller->url(
                $su, $article->getTitle(), array(
                    'realblogID' => $article->getId()
                )
            );
            $t .= '<li>'
                . date($plugin_tx['realblog']['date_format'], $article->getDate())
                . '&nbsp;&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">'
                . $article->getTitle() . '</a></li>';
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
        foreach ($this->articles as $article) {
            $month = date('n', $article->getDate());
            $year = date('Y', $article->getDate());
            if ($month != $currentMonth) {
                $t .= '<h4>' . $this->getMonthName($month) . ' ' . $year . '</h4>';
                $currentMonth = $month;
            }
            $url = $_Realblog_controller->url(
                $su, $article->getTitle(), array(
                    'realblogID' => $article->getId()
                )
            );
            $t .= '<p>'
                . date($plugin_tx['realblog']['date_format'], $article->getDate())
                . '&nbsp;&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">'
                . $article->getTitle() . '</a></p>';
        }
        return $t;
    }
}

?>
