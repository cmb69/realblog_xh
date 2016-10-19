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

class ArchiveView
{
    /**
     * @var array<stdClass>
     */
    protected $articles;

    /**
     * @var int
     */
    protected $articleCount;

    /**
     * @var int
     */
    protected $year;

    /**
     * @param array<stdClass> $articles
     * @param int $articleCount
     * @global Controller $_Realblog_controller
     */
    public function __construct(array $articles, $articleCount)
    {
        global $_Realblog_controller;

        $this->articles = $articles;
        $this->articleCount = $articleCount;
        $this->year = $_Realblog_controller->getYear();
    }

    /**
     * @return string
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
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
            $count = DB::countArchivedArticlesInPeriod(
                mktime(0, 0, 0, 1, 1, $this->year),
                mktime(0, 0, 0, 1, 1, $this->year + 1)
            );
            $t .= $this->renderMonthlyArticleLists($currentMonth);
            if ($count === 0) {
                $t .= $plugin_tx['realblog']['no_topics'];
            }
        } else {
            if ($this->articleCount > 0) {
                $t .= $this->renderSearchResults();
            } else {
                $t .= $plugin_tx['realblog']['no_topics'];
            }
        }
        return $t;
    }

    /**
     * @param int $month
     * @return string
     * @global array $plugin_tx
     */
    private function getMonthName($month)
    {
        global $plugin_tx;

        $monthNames = explode(',', $plugin_tx['realblog']['date_months']);
        return $monthNames[$month - 1];
    }

    /**
     * @param int $back
     * @param int $next
     * @return string
     * @global string $su
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderPagination($back, $next)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $url = $_Realblog_controller->url(
            $su,
            null,
            array('realblog_year' => $back)
        );
        $t = '<div class="realblog_table_paging">'
            . '<a href="' . XH_hsc($url) . '" title="'
            . $plugin_tx['realblog']['tooltip_previousyear'] . '">'
            . '&#9664;</a>&nbsp;&nbsp;';
        $t .= '<b>' . $plugin_tx['realblog']['archive_year']
            . $this->year . '</b>';
        $url = $_Realblog_controller->url(
            $su,
            null,
            array('realblog_year' => $next)
        );
        $t .= '&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
            . $plugin_tx['realblog']['tooltip_nextyear'] . '">'
            . '&#9654;</a>';
        $t .= '</div>';
        return $t;
    }

    /**
     * @param int $currentMonth
     * @return string
     */
    private function renderMonthlyArticleLists($currentMonth)
    {
        $t = '';
        for ($month = $currentMonth; $month >= 1; $month--) {
            $realbloglist = DB::findArchivedArticlesInPeriod(
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
     * @param array<stdClass> $articles
     * @return string
     * @global string $su
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderArticleList(array $articles)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $t = '<ul class="realblog_archive">';
        foreach ($articles as $article) {
            $url = $_Realblog_controller->url(
                $su,
                $article->title,
                array('realblogID' => $article->id)
            );
            $t .= '<li>'
                . date($plugin_tx['realblog']['date_format'], $article->date)
                . '&nbsp;&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">'
                . $article->title . '</a></li>';
        }
        $t .= '</ul>';
        return $t;
    }

    /**
     * @return string
     * @global string $su
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderSearchResults()
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $currentMonth = -1;
        $t = '';
        foreach ($this->articles as $article) {
            $month = date('n', $article->date);
            $year = date('Y', $article->date);
            if ($month != $currentMonth) {
                $t .= '<h4>' . $this->getMonthName($month) . ' ' . $year . '</h4>';
                $currentMonth = $month;
            }
            $url = $_Realblog_controller->url(
                $su,
                $article->title,
                array('realblogID' => $article->id)
            );
            $t .= '<p>'
                . date($plugin_tx['realblog']['date_format'], $article->date)
                . '&nbsp;&nbsp;&nbsp;<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">'
                . $article->title . '</a></p>';
        }
        return $t;
    }
}
