<?php

/**
 * The presentation layer.
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
 * The articles views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticlesView
{
    /**
     * The articles.
     *
     * @var array
     */
    private $_articles;

    /**
     * The categories.
     *
     * @var string
     */
    private $_categories;

    /**
     * The realblog action.
     *
     * @var FIXME
     */
    private $_action;

    /**
     * The number of articles per page.
     *
     * @var int
     */
    private $_articlesPerPage;

    /**
     * Initializes a new instance.
     *
     * @param array  $articles        An array of articles.
     * @param string $categories      FIXME
     * @param FIXME  $action          FIXME
     * @param int    $articlesPerPage The number of articles per page.
     *
     * @return void
     */
    public function __construct($articles, $categories, $action, $articlesPerPage)
    {
        $this->_articles = $articles;
        $this->_categories = (string) $categories;
        $this->_action = $action;
        $this->_articlesPerPage = (int) $articlesPerPage;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global array  The configuration of the plugins.
     * @global string The number of the current page.
     */
    public function render()
    {
        global $plugin_cf, $page;

        $articleCount = count($this->_articles);
        $pageCount = (int) ceil($articleCount / $this->_articlesPerPage);

        if ($page > $pageCount) {
            $page=1;
        }
        if ($page == "" || $page <= 0 || $page == 1) {
            $start_index = 0;
            $page = 1;
        } else {
            $start_index = ($page - 1) * $this->_articlesPerPage;
        }
        $end_index = min($page * $this->_articlesPerPage - 1, $articleCount);

        $mysearch = '';

        if ($articleCount > 0 && $pageCount > 1) {
            if ($pageCount > $page) {
                $next = $page + 1;
                $back = ($page > 1) ? $next - 2 : "1";
            } else {
                $next = $pageCount;
                $back = $pageCount - 1;
            }
        }

        $t = "\n" . '<div class="realblog_show_box">' . "\n";
        $t .= $this->_renderPagination(
            'top', $page, $pageCount, @$back, @$next, $mysearch
        );
        $t .= "\n" . '<div style="clear:both;"></div>';
        $t .= $this->_renderArticlePreviews($start_index, $end_index);
        $t .= $this->_renderPagination(
            'bottom', $page, $pageCount, @$back, @$next, $mysearch
        );
        $t .= '<div style="clear: both"></div></div>';
        return $t;
    }

    /**
     * Renders the article previews.
     *
     * @param int $start The first article to render.
     * @param int $end   The last article to render.
     *
     * @return string (X)HTML.
     */
    private function _renderArticlePreviews($start, $end)
    {
        $articleCount = count($this->_articles);
        $t = '<div id="realblog_entries_preview">';
        for ($i = $start; $i <= $end; $i++) {
            if ($i > $articleCount - 1) {
                $t .= '';
            } else {
                $field = $this->_articles[$i];
                $t .= $this->_renderArticlePreview($field);
            }
        }
        $t .= '<div style="clear: both;"></div>' . '</div>';
        return $t;
    }

    /**
     * Renders an article preview.
     *
     * @param array $field An article record.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     */
    private function _renderArticlePreview($field)
    {
        global $plugin_cf;

        $t = '';
        if (strstr($field[REALBLOG_HEADLINE], '|' . $this->_categories . '|')
            || strstr($field[REALBLOG_STORY], '|' . $this->_categories . '|')
            || $this->_categories == 'all'
            || ($this->_action == "search"
            && strstr($field[REALBLOG_H], '|' . $this->_categories . '|'))
        ) {
            if ($plugin_cf['realblog']['teaser_multicolumns']) {
                $t .= '<div class="realblog_single_entry_preview">'
                    . '<div class="realblog_single_entry_preview_in">';
            }
            $t .= $this->_renderArticleHeading($field);
            $t .= $this->_renderArticleDate($field);
            $t .= "\n" . '<div class="realblog_show_story">' . "\n";
            $t .= evaluate_scripting($field[REALBLOG_HEADLINE]);
            if ($plugin_cf['realblog']['show_read_more_link']
                && $field[REALBLOG_STORY] != ''
            ) {
                $t .= $this->_renderArticleFooter($field);
            }
            $t .= '<div style="clear: both;"></div>' . "\n"
                . '</div>' . "\n";
            if ($plugin_cf['realblog']['teaser_multicolumns']) {
                $t .= '</div>' . "\n" . '</div>' . "\n";
            }
        }
        return $t;
    }

    /**
     * Renders an article heading.
     *
     * @param array $field An article record.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     * @global string The current page number.
     */
    private function _renderArticleHeading($field)
    {
        global $sn, $su, $plugin_tx, $page;

        $t = '<h4>';
        if ($field[REALBLOG_STORY] != '' || XH_ADM) {
            $t .= '<a href="' . $sn . '?' . $su . '&amp;'
                . str_replace(' ', '_', $field[REALBLOG_TITLE])
                . '&amp;realblogaction=view&amp;realblogID='
                . $field[REALBLOG_ID] . '&amp;page=' . $page
                . '" title="' . $plugin_tx['realblog']["tooltip_view"]
                . '">';
        }
        $t .= $field[REALBLOG_TITLE];
        if ($field[REALBLOG_STORY] != '' || XH_ADM) {
            $t .= '</a>';
        }
        $t .= '</h4>' . "\n";
        return $t;
    }

    /**
     * Renders an article date.
     *
     * @param array $field An article record.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderArticleDate($field)
    {
        global $plugin_tx;

        return '<div class="realblog_show_date">'
            . strftime(
                $plugin_tx['realblog']['display_date_format'],
                $field[REALBLOG_DATE]
            )
            . '</div>';
    }

    /**
     * Renders an article footer.
     *
     * @param array $field An article record.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The configuration of the plugins.
     * @global array  The localization of the plugins.
     * @global string The number of the current page.
     */
    private function _renderArticleFooter($field)
    {
        global $sn, $su, $plugin_cf, $plugin_tx, $page;

        $t = '<div class="realblog_entry_footer">';

        if (function_exists('comments_nr')
            && $plugin_cf['realblog']['comments_function']
            && $field[REALBLOG_COMMENTS]
        ) {
            $t .= $this->_renderCommentCount($field);
        }
        $t .= '<p class="realblog_read_more">'
            . '<a href="' . $sn . '?' . $su . '&amp;'
            . str_replace(' ', '_', $field[REALBLOG_TITLE])
            . '&amp;realblogaction=view&amp;realblogID='
            . $field[REALBLOG_ID] . '&amp;page=' . $page
            . '" title="' . $plugin_tx['realblog']["tooltip_view"]
            . '">' . $plugin_tx['realblog']['read_more']
            . '</a></p>' . '</div>';
        return $t;
    }

    /**
     * Renders a comment count.
     *
     * @param array $field An article record.
     *
     * @return string (X)HTML.
     */
    private function _renderCommentCount($field)
    {
        $commentsId = 'comments' . $field[REALBLOG_ID];
        return '<p class="realblog_number_of_comments">'
            . comments_nr($commentsId) . '</p>';
    }

    /**
     * Renders the pagination.
     *
     * @param string $place        A place to render ('top' or 'bottom').
     * @param string $page         A page number.
     * @param int    $pageCount    A page count.
     * @param int    $back         The number of the previous page.
     * @param int    $next         The number of the next page.
     * @param string $searchClause A search clause.
     *
     * @return string (X)HTML.
     */
    private function _renderPagination(
        $place, $page, $pageCount, $back, $next, $searchClause
    ) {
        $articleCount = count($this->_articles);
        $t = '';
        if ($articleCount > 0 && $pageCount > 1) {
            $t .= $this->_renderPageLinks($pageCount, $searchClause);
        }
        if ($this->_wantsNumberOfArticles($place)) {
            $t .= $this->_renderNumberOfArticles();
        }
        if ($articleCount > 0 && $pageCount > 1) {
            $t .= $this->_renderPageOfPages(
                $page, $pageCount, @$back, @$next, $searchClause
            );
        }
        return $t;
    }

    /**
     * Whether the number of articles ought to be displayed.
     *
     * @param string $place A place ('top' or 'bottom').
     *
     * @return bool
     *
     * @global array The configuration of the plugins.
     */
    private function _wantsNumberOfArticles($place)
    {
        global $plugin_cf;

        return !isset($_REQUEST['realblog_story'])
            && $plugin_cf['realblog']['show_numberof_entries_' . $place];
    }

    /**
     * Renders the page links.
     *
     * @param int    $pageCount    A page count.
     * @param string $searchClause A search clause.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     */
    private function _renderPageLinks($pageCount, $searchClause)
    {
        global $sn, $su, $plugin_tx;

        $t = '<div class="realblog_table_paging">';
        for ($i = 1; $i <= $pageCount; $i++) {
            $separator = ($i < $pageCount) ? ' ' : '';
            $t .= '<a href="' . $sn . '?' . $su . '&amp;page=' . $i
                . $searchClause . '" title="'
                . $plugin_tx['realblog']['page_label'] . ' ' . $i . '">['
                . $i . ']</a>' . $separator;
        }
        $t .= '</div>';
        return $t;
    }

    /**
     * Renders the page of pages.
     *
     * @param string $page         The number of the current page.
     * @param int    $pageCount    A page count.
     * @param int    $back         The number of the previous page.
     * @param int    $next         The number of the next page.
     * @param string $searchClause A search clause.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     */
    private function _renderPageOfPages(
        $page, $pageCount, $back, $next, $searchClause
    ) {
        global $sn, $su, $plugin_tx;

        return '<div class="realblog_page_info">'
            . $plugin_tx['realblog']['page_label'] . ' : '
            . '<a href="' . $sn . '?' . $su . '&amp;page=' . @$back
            . $searchClause . '" title="'
            . $plugin_tx['realblog']['tooltip_previous'] . '">'
            . '&#9664;</a>&nbsp;' . $page . ' / ' . $pageCount
            . '&nbsp;' . '<a href="' . $sn . '?' . $su
            . '&amp;page=' . @$next . $searchClause . '" title="'
            . $plugin_tx['realblog']['tooltip_next'] . '">'
            . '&#9654;</a></div>';
    }

    /**
     * Renders the number of articles.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderNumberOfArticles()
    {
        global $plugin_tx;

        return '<div class="realblog_db_info">'
            . $plugin_tx['realblog']['record_count'] . ' : '
            . count($this->_articles) . '</div>';
    }
}

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
     * The action.
     *
     * @var string
     */
    private $_action;

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
     */
    public function __construct($articles)
    {
        $this->_articles = $articles;
        $this->_action = Realblog_getPgParameter('realblogaction');
        $this->_year = Realblog_getPgParameter('realblogYear');
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    public function render()
    {
        global $plugin_tx;

        $t = '';
        $filter_total = 0;
        if ($this->_action != 'search') {
            $currentYear = date('Y');
            if (!isset($this->_year) || $this->_year <= 0
                || $this->_year >= $currentYear || empty($this->_year)
            ) {
                $this->_year = $currentYear;
                $currentMonth = date('n');
            } else {
                $currentMonth = 12;
            }
            $_SESSION['realblogYear'] = $this->_year;
            $next = min($this->_year + 1, $currentYear);
            $back = $this->_year - 1;
            $t .= $this->_renderPagination($back, $next);
            $generalrealbloglist = $this->_selectArticlesInPeriod(
                mktime(0, 0, 0, 1, 1, $this->_year),
                mktime(0, 0, 0, 1, 1, $this->_year + 1)
            );
            $t .= $this->_renderMonthlyArticleLists($currentMonth);
            if (count($generalrealbloglist) == 0) {
                $t .= $plugin_tx['realblog']['no_topics'];
            }
        } else {
            if (count($this->_articles) > 0) {
                $t .= $this->_renderSearchResults();
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
    private function _getMonthName($month)
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
     */
    private function _selectArticlesInPeriod($start, $end)
    {
        $db = Realblog_connect();
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
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     */
    private function _renderPagination($back, $next)
    {
        global $sn, $su, $plugin_tx;

        $t = '<div class="realblog_table_paging">'
            . '<a href="' . $sn . '?' . $su . '&amp;realblogYear='
            . $back . '" title="'
            . $plugin_tx['realblog']['tooltip_previousyear'] . '">'
            . '&#9664;</a>&nbsp;&nbsp;';
        $t .= '<b>' . $plugin_tx['realblog']['archive_year']
            . $this->_year . '</b>';
        $t .= '&nbsp;&nbsp;<a href="' . $sn . '?' . $su
            . '&amp;realblogYear=' . $next . '" title="'
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
    private function _renderMonthlyArticleLists($currentMonth)
    {
        $t = '';
        for ($month = $currentMonth; $month >= 1; $month--) {
            $realbloglist = $this->_selectArticlesInPeriod(
                mktime(0, 0, 0, $month, 1, $this->_year),
                mktime(0, 0, 0, $month + 1, 1, $this->_year)
            );
            $monthName = $this->_getMonthName($month);
            if (count($realbloglist) > 0) {
                $t .= '<h4>' . $monthName . ' ' . $this->_year . '</h4>'
                    . $this->_renderArticleList($realbloglist);
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
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     * @global int    The number of the current page.
     */
    private function _renderArticleList($articles)
    {
        global $sn, $su, $plugin_tx, $page;

        $t = '<ul class="realblog_archive">';
        foreach ($articles as $key => $field) {
            $t .= '<li>'
                . date(
                    $plugin_tx['realblog']['date_format'],
                    $field[REALBLOG_DATE]
                )
                . '&nbsp;&nbsp;&nbsp;<a href="' . $sn . '?'
                . $su . '&amp;'
                . str_replace(' ', '_', $field[REALBLOG_TITLE])
                . '&amp;realblogaction=view&amp;realblogID='
                . $field[REALBLOG_ID] . '&amp;page=' . $page
                . '" title="' . $plugin_tx['realblog']["tooltip_view"]
                . '">' . $field[REALBLOG_TITLE] . '</a></li>';
        }
        $t .= '</ul>';
        return $t;
    }

    /**
     * Renders the search results.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     * @global int    The number of the current page.
     */
    private function _renderSearchResults()
    {
        global $sn, $su, $plugin_tx, $page;

        $currentMonth = -1;
        $t = '';
        foreach ($this->_articles as $key => $field) {
            $month = date('n', $field[REALBLOG_DATE]);
            $year = date('Y', $field[REALBLOG_DATE]);
            if ($month != $currentMonth) {
                $t .= '<h4>' . $this->_getMonthName($month) . ' ' . $year . '</h4>';
                $currentMonth = $month;
            }
            $t .= '<p>'
                . date(
                    $plugin_tx['realblog']['date_format'],
                    $field[REALBLOG_DATE]
                )
                . '&nbsp;&nbsp;&nbsp;<a href="' . $sn . '?' . $su
                . '&amp;'
                . str_replace(' ', '_', $field[REALBLOG_TITLE])
                . '&amp;realblogaction=view&amp;realblogID='
                . $field[REALBLOG_ID] . '&amp;page=' . $page
                . '" title="' . $plugin_tx['realblog']["tooltip_view"]
                . '">' . $field[REALBLOG_TITLE] . '</a></p>';
        }
        return $t;
    }
}

/**
 * The article views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticleView
{
    /**
     * The article ID.
     *
     * @var int
     */
    private $_id;

    /**
     * The article record.
     *
     * @var array
     */
    private $_article;

    /**
     * The article page. Most likely this is always 1.
     *
     * @var int
     */
    private $_page;

    /**
     * Initializes a new instance.
     *
     * @param int    $id      An article ID.
     * @param string $article An article record.
     * @param int    $page    An article page.
     *
     * @return void
     */
    public function __construct($id, $article, $page)
    {
        $this->_id = (int) $id;
        $this->_article = (array) $article;
        $this->_page = (int) $page;
    }

    /**
     * Renders the article.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     */
    public function render()
    {
        global $plugin_cf;

        $html = '<div class="realblog_show_box">'
            . $this->_renderLinks() . $this->_renderHeading()
            . $this->_renderDate() . $this->_renderStory()
            . $this->_renderLinks() . '</div>';
        // output comments in RealBlog
        if ($this->_wantsComments() && $this->_article[REALBLOG_COMMENTS] == 'on') {
            $realblog_comments_id = 'comments' . $this->_id;
            if ($plugin_cf['realblog']['comments_form_protected']) {
                $html .= comments($realblog_comments_id, 'protected');
            } else {
                $html .= comments($realblog_comments_id);
            }
        }
        return $html;
    }

    /**
     * Renders the links.
     *
     * @return string (X)HTML.
     */
    private function _renderLinks()
    {
        $html = '<div class="realblog_buttons">'
            . $this->_renderOverviewLink();
        if (XH_ADM) {
            if ($this->_wantsComments()) {
                $html .= $this->_renderEditCommentsLink();
            }
            $html .= $this->_renderEditEntryLink();
        }
        $html .= '<div style="clear: both;"></div>'
            . '</div>';
        return $html;
    }

    /**
     * Renders the overview link.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     */
    private function _renderOverviewLink()
    {
        global $sn, $su, $plugin_tx;

        if ($this->_article[REALBLOG_STATUS] == 2) {
            $url = $sn . '?' . $su . '&amp;realblogYear='
                . $_SESSION['realblogYear'];
            $text = $plugin_tx['realblog']['archiv_back'];
        } else {
            $url = $sn . '?' . $su . '&amp;page=' . $this->_page;
            $text = $plugin_tx['realblog']['blog_back'];
        }
        return '<span class="realblog_button">'
            . '<a href="' . $url . '">' . $text . '</a></span>';
    }

    /**
     * Renders the edit entry link.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderEditEntryLink()
    {
        global $sn, $plugin_tx;

        return '<span class="realblog_button">'
            . '<a href="' . $sn . '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=modify_realblog&amp;realblogID='
            . $this->_id . '">'
            . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
    }

    /**
     * Renders the edit comments link.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderEditCommentsLink()
    {
        global $sn, $plugin_tx;

        return '<span class="realblog_button">'
            . '<a href="' . $sn . '?&amp;comments&amp;admin=plugin_main'
            . '&amp;action=plugin_text&amp;selected=comments'
            . $this->_id . '.txt">'
            . $plugin_tx['realblog']['comment_edit'] . '</a></span>';
    }

    /**
     * Renders the article heading.
     *
     * @return string (X)HTML.
     *
     * @todo Heed $cf[menu][levels].
     */
    private function _renderHeading()
    {
        return '<h4>' . $this->_article[REALBLOG_TITLE] . '</h4>';
    }

    /**
     * Renders the article date.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderDate()
    {
        global $plugin_tx;

        $date = strftime(
            $plugin_tx['realblog']['display_date_format'],
            $this->_article[REALBLOG_DATE]
        );
        return '<div class="realblog_show_date">' . $date . '</div>';
    }

    /**
     * Renders the article story.
     *
     * @return string (X)HTML.
     */
    private function _renderStory()
    {
        $story = $this->_article[REALBLOG_STORY] != ''
            ? $this->_article[REALBLOG_STORY]
            : $this->_article[REALBLOG_HEADLINE];
        return '<div class="realblog_show_story_entry">'
            // FIXME: stripslashes() ?
            . stripslashes(evaluate_scripting($story))
            . '</div>';
    }

    /**
     * Returns whether comments are enabled.
     *
     * @return bool
     *
     * @global array The configuration of the plugins.
     */
    private function _wantsComments()
    {
        global $plugin_cf;

        return $plugin_cf['realblog']['comments_function']
            && function_exists('comments');
    }
}

/**
 * The search form views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_SearchFormView
{
    /**
     * The selected year.
     *
     * @var int
     */
    private $_year;

    /**
     * Initializes a new instance.
     *
     * @param int $year A year.
     *
     * @return void
     */
    public function __construct($year)
    {
        $this->_year = (int) $year;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global string The (X)HTML fragment to insert into the head element.
     * @global array  The localization of the core.
     * @global array  The localization of the plugins.
     */
    public function render()
    {
        global $sn, $su, $hjs, $tx, $plugin_tx;

        $hjs .= $this->_renderToggleScript();
        return '<form name="realblogsearch" method="post" action="' . $sn . "?"
            . $su . '">'
            . $this->_renderSearchToggle()
            . '<div id="searchblock" style="display: none">'
            . $this->_renderHiddenInputs()
            . '<p class="realblog_search_hint">'
            . $plugin_tx['realblog']['search_hint'] . '</p>'
            . '<table style="width: 100%;">'
            . $this->_renderInputRow('title')
            . '<tr>'
            . '<td style="width: 30%;">&nbsp;</td>'
            . '<td>' . $this->_renderOperatorRadio('and') . '&nbsp;&nbsp;&nbsp;'
            . $this->_renderOperatorRadio('or') . '</td>'
            . '</tr>'
            . $this->_renderInputRow('story')
            . '<tr><td colspan="2" style="text-align: center;">'
            . tag(
                'input type="submit" name="send" value="'
                . $tx['search']['button'] . '"'
            )
            . '</td></tr>'
            . '</table></div></form>';
    }

    /**
     * Renders the search toggle.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the core.
     * @global array The localization of the plugins.
     */
    private function _renderSearchToggle()
    {
        global $pth, $tx, $plugin_tx;

        $src = $pth['folder']['plugins'] . 'realblog/images/btn_expand.gif';
        return '<div id="enablesearch">'
            // FIXME: javascript protocol
            . '<a href="javascript:realblog_showSearch()">'
            . tag(
                'img id="btn_img" alt="searchbuttonimg" src="' . $src
                . '" title="' . $plugin_tx['realblog']['tooltip_showsearch']
                . '" style="border: 0"'
            )
            . '</a>&nbsp;<b>' . $tx['search']['button'] . '</b></div>';
    }

    /**
     * Renders the toggle script.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     *
     * @todo Escape JS strings.
     */
    private function _renderToggleScript()
    {
        global $pth, $plugin_tx;

        $imageFolder = $pth['folder']['plugins'] . 'realblog/images/';
        return <<<EOT
<script type="text/javascript">/* <![CDATA[ */
function realblog_showSearch() {
    var searchblock = document.getElementById("searchblock"),
        toggle = document.getElementById("btn_img");

    if (searchblock.style.display == "none") {
        toggle.title = "{$plugin_tx['realblog']['tooltip_hidesearch']}";
        toggle.src = "{$imageFolder}btn_collapse.gif";
        searchblock.style.display = "";
    } else {
        toggle.title = "{$plugin_tx['realblog']['tooltip_showsearch']}";
        toggle.src = "{$imageFolder}btn_expand.gif";
        searchblock.style.display = "none";
    }
}
/* ]]> */s</script>
EOT;
    }

    /**
     * Renders the hidden input fields.
     *
     * @return string (X)HTML.
     */
    private function _renderHiddenInputs()
    {
        return $this->_renderHiddenInput('realblogaction', 'search')
            . $this->_renderHiddenInput('realblogYear', $this->_year);
    }

    /**
     * Renders a hidden input field.
     *
     * @param string $name  A name.
     * @param string $value A value.
     *
     * @return string (X)HTML.
     */
    private function _renderHiddenInput($name, $value)
    {
        return tag(
            'input type="hidden" name="' . $name . '" value="' . $value . '"'
        );
    }

    /**
     * Renders an input row.
     *
     * @param string $which Which row to render ('title' or 'story').
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderInputRow($which)
    {
        global $plugin_tx;

        return '<tr><td style="width: 30%;" class="realblog_search_text">'
            . $plugin_tx['realblog']["{$which}_label"] . ' '
            . $plugin_tx['realblog']['search_contains'] . ':' . '</td>'
            . '<td>' . $this->_renderOperatorSelect("{$which}_operator")
            . $this->_renderInput("realblog_$which") . '</td></tr>';
    }

    /**
     * Renders an input field.
     *
     * @param string $name A name.
     *
     * @return string (X)HTML.
     */
    private function _renderInput($name)
    {
        return tag(
            'input type="text" name="' . $name . '" size="35"'
            . ' class="realblog_search_input" maxlength="64"'
        );
    }

    /**
     * Renders an operator select element.
     *
     * @param string $name A name.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderOperatorSelect($name)
    {
        global $plugin_tx;

        return '<select name="' . $name . '" style="display: none">'
            . '<option value="2" selected="selected">'
            . $plugin_tx['realblog']['search_contains'] . '</option>'
            . '</select>';
    }

    /**
     * Renders an operator radio element.
     *
     * @param string $which Which operator to render ('and' or 'or').
     *
     * @return string (X)HTML.
     *
     * @global array The localiaztion of the plugins.
     */
    private function _renderOperatorRadio($which)
    {
        global $plugin_tx;

        $checked = ($which == 'or') ? ' checked="checked"' : '';
        return '<label>'
            . tag(
                'input type="radio" name="operator_2"'
                . ' value="' . strtoupper($which) . '"' . $checked
            )
            . '&nbsp;' . $plugin_tx['realblog']["search_$which"] . '</label>';
    }
}

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
     * @global string The calendar date format.
     * @global int    The number of the current page.
     * @global string Whether filtering of unpublished articles is enabled.
     * @global string Whether filtering of published articles is enabled.
     * @global string Whether filtering of archived articles is enabled.
     */
    private function _handleMainAdministration()
    {
        global $pth, $o, $action, $cal_format, $page, $filter1, $filter2, $filter3;

        if (!is_writable($pth['folder']['content'] . 'realblog/realblog.txt')) {
            $o .= $this->_renderDatafileError();
        } else {
            $cal_format = Realblog_getCalendarDateFormat();
            $page = Realblog_getPgParameter('page');
            $filter1 = Realblog_getPgParameter('filter1');
            $filter2 = Realblog_getPgParameter('filter2');
            $filter3 = Realblog_getPgParameter('filter3');
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
     * @global int   The number of the current page.
     * @global int   The number of pages.
     */
    private function _renderArticles()
    {
        global $plugin_cf, $plugin_tx, $page, $page_total;

        $records = $this->_db->selectWhere(
            'realblog.txt', $this->_getFilterClause(), -1,
            new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
        );

        $page_record_limit = $plugin_cf['realblog']['admin_records_page'];
        $db_total_records = count($records);
        $page_total = ceil($db_total_records / $page_record_limit);
        $page = max(min((int) $page, $page_total), 1);
        $start_index = ($page - 1) * $page_record_limit;

        $view = new Realblog_ArticlesAdminView(
            $records, $page_record_limit, $start_index
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
     * @global int    The number of the current page.
     */
    private function _renderArticle()
    {
        global $action, $page;

        init_editor(array('realblog_headline_field', 'realblog_story_field'));
        return Realblog_form(
            Realblog_getPgParameter('realblogID'), $action, $page
        );
    }

    /**
     * Adds an article.
     *
     * @return string (X)HTML.
     *
     * @global string The page title.
     * @global array  The localization of the plugins.
     * @global int    The number of the current page.
     */
    private function _addArticle()
    {
        global $title, $plugin_tx, $page;

        $article = $this->_getArticleFromParameters();
        $this->_db->insertWithAutoId('realblog.txt', REALBLOG_ID, $article);
        $title = $plugin_tx['realblog']['tooltip_add'];
        $info = $plugin_tx['realblog']['story_added'];
        return Realblog_dbconfirm($title, $info, $page);
    }

    /**
     * Modifies an article.
     *
     * @return string (X)HTML.
     *
     * @global string The page title.
     * @global array  The localization of the plugins.
     * @global int    The number of the current page.
     */
    private function _modifyArticle()
    {
        global $title, $plugin_tx, $page;

        $article = $this->_getArticleFromParameters();
        $this->_db->updateRowById('realblog.txt', REALBLOG_ID, $article);
        $title = $plugin_tx['realblog']['tooltip_modify'];
        $info = $plugin_tx['realblog']['story_modified'];
        return Realblog_dbconfirm($title, $info, $page);
    }

    /**
     * Deletes an article.
     *
     * @return string (X)HTML.
     *
     * @global string The page title.
     * @global array  The localization of the plugins.
     * @global int    The number of the current page.
     */
    private function _deleteArticle()
    {
        global $title, $plugin_tx, $page;

        $page = $_SESSION['page'];
        $id = Realblog_getPgParameter('realblog_id');
        $this->_db->deleteWhere(
            'realblog.txt', new SimpleWhereClause(REALBLOG_ID, '=', $id),
            INTEGER_COMPARISON
        );
        $title = $plugin_tx['realblog']['tooltip_delete'];
        $info = $plugin_tx['realblog']['story_deleted'];
        return Realblog_dbconfirm($title, $info, $page);
    }

    /**
     * Renders the change status confirmation.
     *
     * @return string (X)HTML.
     *
     * @global int The number of the current page.
     */
    private function _confirmChangeStatus()
    {
        global $page;

        $_SESSION['page'] = $page;
        $view = new Realblog_ChangeStatusView();
        return $view->render();
    }

    /**
     * Changes the article status.
     *
     * @return string (X)HTML.
     *
     * @global string The page title.
     * @global array  The localization of the plugins.
     * @global int    The number of the current page.
     */
    private function _changeStatus()
    {
        global $title, $plugin_tx, $page;

        if (isset($_SESSION['page'])) {
            $page = $_SESSION['page'];
        }
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
            return Realblog_dbconfirm($title, $info, $page);
        } else {
            $title = $plugin_tx['realblog']['tooltip_changestatus'];
            $info = $plugin_tx['realblog']['nochangestatus_done'];
            return Realblog_dbconfirm($title, $info, $page);
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
     * @global string The page title.
     * @global array  The localization of the plugins.
     * @global int    The number of the current page.
     */
    private function _deleteArticles()
    {
        global $title, $plugin_tx, $page;

        $ids = Realblog_getPgParameter('realblogtopics');
        foreach ($ids as $id) {
            $this->_db->deleteWhere(
                'realblog.txt', new SimpleWhereClause(REALBLOG_ID, '=', $id),
                INTEGER_COMPARISON
            );
        }
        $title = $plugin_tx['realblog']['tooltip_deleteall'];
        $info = $plugin_tx['realblog']['deleteall_done'];
        return Realblog_dbconfirm($title, $info, $page);
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
        $article[REALBLOG_DATE] = Realblog_makeTimestampDates(
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
        $article[REALBLOG_STARTDATE] = Realblog_makeTimestampDates(
            Realblog_getPgParameter('realblog_startdate')
        );
        $article[REALBLOG_ENDDATE] = Realblog_makeTimestampDates(
            Realblog_getPgParameter('realblog_enddate')
        );
        $article[REALBLOG_STATUS] = Realblog_getPgParameter('realblog_status');
        $article[REALBLOG_RSSFEED] = Realblog_getPgParameter('realblog_rssfeed');
        $article[REALBLOG_COMMENTS] = Realblog_getPgParameter('realblog_comments');
        return $article;
    }

    /**
     * Returns the current filter clause.
     *
     * @return WhereClause
     *
     * @global string Whether filtering of unpublished articles is enabled.
     * @global string Whether filtering of published articles is enabled.
     * @global string Whether filtering of archived articles is enabled.
     */
    private function _getFilterClause()
    {
        global $filter1, $filter2, $filter3;

        $filterClause = null;
        foreach (array($filter1, $filter2, $filter3) as $i => $value) {
            if ($value == 'on') {
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

/**
 * The info views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_InfoView
{
    /**
     * Renders the plugin info.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        return '<h1>Realblog</h1>'
            . $this->_renderLogo()
            . '<p>Version: ' . REALBLOG_VERSION . '</p>'
            . $this->_renderCopyright() . $this->_renderLicense();
    }

    /**
     * Renders the plugin logo.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     */
    private function _renderLogo()
    {
        global $pth, $plugin_tx;

        return tag(
            'img src="' . $pth['folder']['plugins']
            . 'realblog/realblog.png" class="realblog_logo"'
            . ' alt="' . $plugin_tx['realblog']['alt_logo'] . '"'
        );
    }

    /**
     * Renders the copyright info.
     *
     * @return string (X)HTML.
     */
    private function _renderCopyright()
    {
        return '<p>Copyright &copy; 2006-2010 Jan Kanters' . tag('br')
            . 'Copyright &copy; 2010-2014 '
            . '<a href="http://www.ge-webdesign.de/" target="_blank">'
            . 'Gert Ebersbach</a>' . tag('br')
            . 'Copyright &copy; 2014 '
            . '<a href="http://3-magi.net/" target="_blank">'
            . 'Christoph M. Becker</a></p>';
    }

    /**
     * Renders the license info.
     *
     * @return string (X)HTML.
     */
    private function _renderLicense()
    {
        return <<<EOT
<p class="realblog_license">This program is free software: you can redistribute
it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.</p>
<p class="realblog_license">This program is distributed in the hope that it will
be useful, but <em>without any warranty</em>; without even the implied warranty
of <em>merchantability</em> or <em>fitness for a particular purpose</em>. See
the GNU General Public License for more details.</p>
<p class="realblog_license">You should have received a copy of the GNU General
Public License along with this program. If not, see <a
href="http://www.gnu.org/licenses/"
target="_blank">http://www.gnu.org/licenses/</a>.</p>
EOT;
    }
}

/**
 * The articles administration views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticlesAdminView
{
    /**
     * The path of the plugin image folder.
     *
     * @var string
     */
    private $_imageFolder;

    /**
     * The articles.
     *
     * @var array
     */
    private $_articles;

    /**
     * The number of articles per page.
     *
     * @var int
     */
    private $_articlesPerPage;

    /**
     * The start index.
     *
     * @var int
     */
    private $_startIndex;

    /**
     * Initializes a new instance.
     *
     * @param array $articles        An array of articles.
     * @param int   $articlesPerPage The number of articles per page.
     * @param int   $startIndex      A start index.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
    public function __construct($articles, $articlesPerPage, $startIndex)
    {
        global $pth;

        $this->_imageFolder =  $pth['folder']['plugins'] . 'realblog/images/';
        $this->_articles = $articles;
        $this->_articlesPerPage = (int) $articlesPerPage;
        $this->_startIndex = (int) $startIndex;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The current page number.
     */
    public function render()
    {
        global $sn, $page;

        $o = $this->_renderFilterForm();
        // Display table header
        $o .= "\n" . '<div>' . "\n"
            . '<form method="post" action="' . $sn . '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main">' . "\n"
            . '<table class="realblog_table" width="100%" cellpadding="0"'
            . ' cellspacing="0">';
        $o .= $this->_renderTableHead();

        $end_index = $page * $this->_articlesPerPage - 1;

        // Display table lines
        for ($i = $this->_startIndex; $i <= $end_index; $i++) {
            if ($i <= count($this->_articles) - 1) {
                $field = $this->_articles[$i];
                $o .= $this->_renderRow($field);
            }
        }

        $o .= '</table></div>';
        $o .= tag('input type="hidden" name="page" value="' . $page . '"')
            . '</form>';
        $o .= $this->_renderNavigation();
        return $o;
    }

    /**
     * Renders the filter form.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderFilterForm()
    {
        global $sn, $plugin_tx;

        $url = $sn . '?&realblog&admin=plugin_main&action=plugin_text';
        $html = '<form id="realblog_filter" method="post" action="'
            . XH_hsc($url) . '">';
        $states = array('readyforpublishing', 'published', 'archived');
        foreach ($states as $i => $state) {
            $html .= $this->_renderFilterCheckbox($i + 1, $state);
        }
        $html .= '<button>' . $plugin_tx['realblog']['btn_filter'] . '</button>'
            . '</form>';
        return $html;
    }

    /**
     * Renders a filter checkbox and its label.
     *
     * @param int    $number A filter number.
     * @param string $name   A filter name.
     *
     * @return string (X)HTML.
     *
     * @global string Whether filter 1 is enabled.
     * @global string Whether filter 2 is enabled.
     * @global string Whether filter 3 is enabled.
     */
    private function _renderFilterCheckbox($number, $name)
    {
        global $plugin_tx, $filter1, $filter2, $filter3;

        $filterVar = 'filter' . $number;
        $checked = ($$filterVar == 'on') ? ' checked="checked"' : '';
        return '<label>'
            . tag(
                'input type="checkbox" name="filter' . $number . '" ' . $checked
                . '"'
            )
            . $plugin_tx['realblog'][$name] . '</label>';
    }

    /**
     * Renders the head of the table.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderTableHead()
    {
        global $sn, $plugin_tx;

        return '<tr>'
            . '<td class="realblog_table_header" align="center">'
            . tag(
                'input type="image" align="middle" src="'
                . $this->_imageFolder . 'delete.png" name="action"'
                . ' value="batchdelete" title="'
                . $plugin_tx['realblog']['tooltip_deleteall'] . '"'
            )
            . '</td>'
            . '<td class="realblog_table_header" align="center">'
            . tag(
                'input type="image" align="middle" src="' . $this->_imageFolder
                . 'change-status.png" name="action" value="change_status"'
                . ' title="' . $plugin_tx['realblog']['tooltip_changestatus']
                . '"'
            )
            . '</td>'
            . '<td class="realblog_table_header" align="center">'
            . '<a href="' . $sn . '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main&amp;action=add_realblog">'
            . tag(
                'img src="' . $this->_imageFolder . 'add.png"'
                . ' align="middle" title="'
                . $plugin_tx['realblog']['tooltip_add'] . '" alt=""'
            )
            . '</a></td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . $plugin_tx['realblog']['id_label'] . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . $plugin_tx['realblog']['date_label'] . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . 'Status' . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">RSS Feed'
            . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . $plugin_tx['realblog']['comments_onoff'] . '</td>' . "\n"
            . '</tr>';
    }

    /**
     * Renders the pagination navigation.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string Whether the filter form has been submitted.
     * @global string Whether filter 1 is enabled.
     * @global string Whether filter 2 is enabled.
     * @global string Whether filter 3 is enabled.
     * @global string The current page number.
     * @global int    The number of pages.
     * @global int    The number of articles.
     */
    private function _renderNavigation()
    {
        global $sn, $plugin_tx, $filter, $filter1, $filter2, $filter3, $page,
            $page_total;

        $db_total_records = count($this->_articles);
        $tmp = ($db_total_records > 0)
            ? $plugin_tx['realblog']['page_label'] . ' : ' . $page .  ' / '
                . $page_total
            : '';
        $o = '<div class="realblog_paging_block">'
            . '<div class="realblog_db_info">'
            . $plugin_tx['realblog']['record_count'] . ' : '
            . $db_total_records . '</div>'
            . '<div class="realblog_page_info">' . $tmp . '</div>';

        if ($db_total_records > 0 && $page_total > 1) {
            if ($page_total > $page) {
                $next = $page + 1;
                $back = ($page > 1) ? ($next - 2) : '1';
            } else {
                $next = $page_total;
                $back = $page_total - 1;
            }
            $o .= '<div class="realblog_table_paging">'
                . '<a href="' . $sn . '?&amp;' . 'realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                . $back . '&amp;filter1=' . $filter1 . '&amp;filter2='
                . $filter2 . '&amp;filter3=' . $filter3 . '" title="'
                . $plugin_tx['realblog']['tooltip_previous'] . '">'
                . '&#9664;</a>&nbsp;&nbsp;';
            for ($i = 1; $i <= $page_total; $i++) {
                $separator = ($i < $page_total) ? ' ' : '';
                $o .= '<a href="' . $sn . '?&amp;' . 'realblog'
                    . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                    . $i . '&amp;filter1=' . $filter1 . '&amp;filter2='
                    . $filter2 . '&amp;filter3=' . $filter3 . '" title="'
                    . $plugin_tx['realblog']['page_label']
                    . ' ' . $i . '">[' . $i . ']</a>' . $separator;
            }
            $o .= '&nbsp;&nbsp;<a href="' . $sn . '?&amp;' . 'realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                . $next . '&amp;filter1=' . $filter1 . '&amp;filter2='
                . $filter2 . '&amp;filter3=' . $filter3 . '" title="'
                . $plugin_tx['realblog']['tooltip_next']
                . '">'
                . '&#9654;</a>';
            $o .= '</div>';
        }
        $o .= '</div>';
        return $o;
    }

    /**
     * Renders a row.
     *
     * @param array $field An article record.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string The current page number.
     */
    private function _renderRow($field)
    {
        global $sn, $plugin_tx, $page;

        return '<tr>'
            . '<td class="realblog_table_line" align="center">'
            . tag(
                'input type="checkbox" name="realblogtopics[]"'
                . ' value="' . $field[REALBLOG_ID] . '"'
            )
            . '</td>'
            . '<td class="realblog_table_line" valign="top"'
            . ' align="center">'
            . '<a href="' . $sn. '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main&amp;action=delete_realblog'
            . '&amp;realblogID=' . $field[REALBLOG_ID] . '&amp;page='
            . $page . '">'
            . tag(
                'img src="' . $this->_imageFolder . 'delete.png"'
                . ' align="center" title="'
                . $plugin_tx['realblog']['tooltip_delete'] . '" alt=""'
            )
            . '</a></td>'
            . '<td class="realblog_table_line" valign="top"'
            . ' align="center">'
            . '<a href="' . $sn . '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main&amp;action=modify_realblog'
            . '&amp;realblogID=' . $field[REALBLOG_ID] . '&amp;page='
            . $page . '">'
            . tag(
                'img src="' . $this->_imageFolder . 'edit.png"'
                . ' align="center" title="'
                . $plugin_tx['realblog']['tooltip_modify'] . '" alt=""'
            )
            . '</a></td>'
            . '<td class="realblog_table_line" valign="top"'
            . ' align="center"><b>' . $field[REALBLOG_ID] . '</b></td>'
            . '<td valign="top" style="text-align: center;"'
            . ' class="realblog_table_line">'
            . date(
                $plugin_tx['realblog']['date_format'], $field[REALBLOG_DATE]
            )
            . '</td>' . "\n"
            . '<td class="realblog_table_line" valign="top"'
            . ' style="text-align: center;"><b>'
            . $field[REALBLOG_STATUS] . '</b></td>' . "\n"
            . '<td class="realblog_table_line realblog_onoff"'
            . ' valign="top" style="text-align: center;">'
            . $field[REALBLOG_RSSFEED] . '</td>' . "\n"
            . '<td class="realblog_table_line realblog_onoff"'
            . ' valign="top" style="text-align: center;">'
            . $field[REALBLOG_COMMENTS] . '</td>' . "\n"
            . '</tr>' . "\n" . '<tr>' . "\n"
            . '<td colspan="8" valign="top"'
            . ' class="realblog_table_title"><span>'
            . $field[REALBLOG_TITLE] . '</span></td></tr>';
    }
}

/**
 * The article administration views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticleAdminView
{
    /**
     * The id of the current article.
     *
     * @var string
     */
    private $_realblogId;

    /**
     * The date of the article.
     *
     * @var FIXME
     */
    private $_realblogDate;

    /**
     * The publishing date of the article.
     *
     * @var FIXME
     */
    private $_startDate;

    /**
     * The archiving date of the article.
     *
     * @var FIXME
     */
    private $_endDate;

    /**
     * The status of the article.
     *
     * @var int
     */
    private $_status;

    /**
     * FIXME
     *
     * @var FIXME
     */
    private $_commentsChecked;

    /**
     * FIXME
     *
     * @var FIXME
     */
    private $_rssChecked;

    /**
     * The title of the article.
     *
     * @var string
     */
    private $_realBlogTitle;

    /**
     * The headline (teaser) of the article.
     *
     * @var string
     */
    private $_headline;

    /**
     * The story (body) of the article.
     *
     * @var string
     */
    private $_story;

    /**
     * The requested action.
     *
     * @var string
     */
    private $_action;

    /**
     * FIXME
     *
     * @var FIXME
     */
    private $_retPage;

    /**
     * The paths of the plugin image folder.
     *
     * @var string
     */
    private $_imageFolder;

    /**
     * Initializes a new instance.
     *
     * @param string $realblogId      The id of the current article.
     * @param FIXME  $realblogDate    The date of the article.
     * @param FIXME  $startDate       The publishing date of the article.
     * @param FIXME  $endDate         The archiving date of the article.
     * @param int    $status          The status of the article.
     * @param FIXME  $commentsChecked FIXME.
     * @param FIXME  $rssChecked      FIXME.
     * @param string $realBlogTitle   The title of the article.
     * @param string $headline        The teaser of the article.
     * @param string $story           The body of the article.
     * @param string $action          The requested action.
     * @param FIXME  $ret_page        FIXME.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
    public function __construct(
        $realblogId, $realblogDate, $startDate, $endDate, $status,
        $commentsChecked, $rssChecked, $realBlogTitle, $headline, $story,
        $action, $ret_page
    ) {
        global $pth;

        $this->_realblogId = $realblogId;
        $this->_realblogDate = $realblogDate;
        $this->_startDate = $startDate;
        $this->_endDate = $endDate;
        $this->_status = $status;
        $this->_commentsChecked = $commentsChecked;
        $this->_rssChecked = $rssChecked;
        $this->_realblogTitle = $realBlogTitle;
        $this->_headline = $headline;
        $this->_story = $story;
        $this->_action = $action;
        $this->_retPage = $ret_page;
        $this->_imageFolder = $pth['folder']['plugins'] . 'realblog/images/';
    }

    /**
     * Renders the article administration view.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string The title of the page.     *
     */
    public function render()
    {
        global $sn, $plugin_tx, $title;

        $t = '<div class="realblog_fields_block"><h1>Realblog &ndash; '
            . $title . '</h1>';
        $t .= '<form name="realblog" method="post" action="' . $sn . '?&amp;'
            . 'realblog' . '&amp;admin=plugin_main">'
            . $this->_renderHiddenFields();
        $t .= '<table width="100%">';
        $t .= '<tr><td width="30%"><span class="realblog_date_label">'
            . $plugin_tx['realblog']['date_label'] . '</span></td>'
            . '<td width="5%">&nbsp;</td><td width="30%">'
            . '<span class="realblog_date_label">'
            . $plugin_tx['realblog']['startdate_label'] . '</span></td>'
            . '<td width="5%">&nbsp;</td><td width="30%">'
            . '<span class="realblog_date_label">'
            . $plugin_tx['realblog']['enddate_label'] . '</span></td></tr><tr>';
        $t .= '<td width="30%" valign="top">'
            . $this->_renderDate()
            . '</td><td width="5%">&nbsp;</td>';
        $t .= '<td width="30%" valign="top">' . $this->_renderPublishingDate();
        $t .= '</td><td width="5%">&nbsp;</td>';
        $t .= '<td width="30%" valign="top">' . $this->_renderArchiveDate()
            . '</td></tr><tr>';

        $t .= $this->_renderCalendarScript();

        $t .= '<td width="30%"><span class="realblog_date_label">'
            . $plugin_tx['realblog']['status_label'] . '</span></td>'
            . '<td width="5%">&nbsp;</td>'
            . '<td width="30%">&nbsp;</span></td>'
            . '<td width="5%">&nbsp;</td>'
            . '<td width="30%"><span>&nbsp;</span></td></tr>'
            . '<tr>';
        $t .= '<td width="30%" valign="top">' . $this->_renderStatusSelect()
            . '</td>';
        $t .= '<td width="5%">&nbsp;</td><td width="30%" valign="top">'
            . $this->_renderCommentsCheckbox() . '</td>';
        $t .= '<td width="5%">&nbsp;</td><td width="30%" valign="top">'
            . $this->_renderFeedCheckbox() . '</td></tr>';
        $t .= '</table>';
        $t .= '<h4>' . $plugin_tx['realblog']['title_label'] . '</h4>';
        $t .= tag(
            'input type="text" value="' . @$this->_realblogTitle
            . '" name="realblog_title" size="70"'
        );
        $t .= $this->_renderHeadline() . $this->_renderStory()
            . $this->_renderSubmitButtons() . '</form>' . '</div>';
        return $t;
    }

    /**
     * Renders the hidden fields.
     *
     * @return string (X)HTML.
     */
    private function _renderHiddenFields()
    {
        $html = '';
        $fields = array(
            'page' => $this->_retPage,
            'realblog_id' => $this->_realblogId,
            'action' => 'do_' . $this->_getVerb()
        );
        foreach ($fields as $name => $value) {
            $html .= $this->_renderHiddenField($name, $value);
        }
        return $html;
    }

    /**
     * Renders a hidden field.
     *
     * @param string $name  A field name.
     * @param string $value A field value.
     *
     * @return string (X)HTML.
     */
    private function _renderHiddenField($name, $value)
    {
        return tag(
            'input type="hidden" name="' . $name . '" value="' . $value . '"'
        );
    }

    /**
     * Renders the date input.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderDate()
    {
        global $plugin_tx;

        $html = tag(
            'input type="text" name="realblog_date" id="date1" value="'
            . $this->_realblogDate . '" size="10" maxlength="10"'
            . ' onfocus="this.blur()"'
        );
        $html .= '&nbsp;'
            . tag(
                'img src="' . $this->_imageFolder . 'calendar.png"'
                . ' style="margin-left:1px;margin-bottom:-3px;"'
                . ' id="trig_date1" title="'
                . $plugin_tx['realblog']['tooltip_datepicker'] . '" alt=""'
            );
        return $html;
    }

    /**
     * Renders the publishing date input.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    private function _renderPublishingDate()
    {
        global $plugin_cf, $plugin_tx;

        if ($plugin_cf['realblog']['auto_publish']) {
            $html = tag(
                'input type="text" name="realblog_startdate" id="date2"'
                . ' value="' . $this->_startDate . '" size="10" maxlength="10"'
                . ' onfocus="this.blur()"'
            );
            $html .= '&nbsp;'
                . tag(
                    'img src="' . $this->_imageFolder . 'calendar.png"'
                    . ' style="margin-left:1px;margin-bottom:-3px;"'
                    . ' id="trig_date2" title="'
                    . $plugin_tx['realblog']['tooltip_datepicker'] . '" alt=""'
                );
        } else {
            $html = $plugin_tx['realblog']['startdate_hint'];
        }
        return $html;
    }

    /**
     * Renders the archiving date input.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    private function _renderArchiveDate()
    {
        global $plugin_cf, $plugin_tx;

        if ($plugin_cf['realblog']['auto_archive']) {
            $html = tag(
                'input type="text" name="realblog_enddate" id="date3"'
                . ' value="' . $this->_endDate . '" size="10" maxlength="10"'
                . ' onfocus="this.blur()"'
            );
            $html .= '&nbsp;'
                . tag(
                    'img src="' . $this->_imageFolder . 'calendar.png"'
                    . ' style="margin-left:1px;margin-bottom:-3px;"'
                    . ' id="trig_date3" title="'
                    . $plugin_tx['realblog']['tooltip_datepicker'] . '" alt=""'
                );
        } else {
            $html = $plugin_tx['realblog']['enddate_hint'];
        }
        return $html;
    }

    /**
     * Renders the calendar script.
     *
     * @return string (X)HTML.
     *
     * @return array The configuration of the plugins.
     */
    private function _renderCalendarScript()
    {
        global $plugin_cf;

        $html = '<script type="text/javascript">/* <![CDATA[ */'
            . $this->_renderCalendarInitialization(1);
        if ($plugin_cf['realblog']['auto_publish']) {
            $html .= $this->_renderCalendarInitialization(2);
        }
        if ($plugin_cf['realblog']['auto_archive']) {
            $html .= $this->_renderCalendarInitialization(3);
        }
        $html .= '/* ]]> */</script>';
        return $html;
    }

    /**
     * Renders a calendar initialization.
     *
     * @param string $num A date input number.
     *
     * @return string (X)HTML.
     *
     * @global string The date format.
     */
    private function _renderCalendarInitialization($num)
    {
        global $cal_format;

        return <<<EOT
Calendar.setup({
    inputField: "date$num",
    ifFormat: "$cal_format",
    button: "trig_date$num",
    align: "Br",
    singleClick: true,
    firstDay: 1,
    weekNumbers: false,
    electric: false,
    showsTime: false,
    timeFormat: "24"
});
EOT;
    }

    /**
     * Renders the status select.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderStatusSelect()
    {
        global $plugin_tx;

        $states = array('readyforpublishing', 'published', 'archived', 'backuped');
        $html = '<select name="realblog_status">';
        foreach ($states as $i => $state) {
            $html .= '<option value="' . $i . '" ' . @$this->_status[$i] . '>'
                . $plugin_tx['realblog'][$state] . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Renders the comments checkbox.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderCommentsCheckbox()
    {
        global $plugin_tx;

        return '<label>'
            . tag(
                'input type="checkbox" name="realblog_comments" '
                . @$this->_commentsChecked
            )
            . '&nbsp;<span>' . $plugin_tx['realblog']['comment_label']
            . '</span></label>';
    }

    /**
     * Renders the feed checkbox.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderFeedCheckbox()
    {
        global $plugin_tx;

        return '<label>'
            . tag(
                'input type="checkbox" name="realblog_rssfeed" '
                . @$this->_rssChecked
            )
            . '&nbsp;<span>' . $plugin_tx['realblog']['rss_label']
            . '</span></label>';
    }

    /**
     * Renders the headline (teaser).
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderHeadline()
    {
        global $plugin_tx;

        return '<h4>' . $plugin_tx['realblog']['headline_label'] . '</h4>'
            . '<p><b>Script for copy &amp; paste:</b></p>'
            . '{{{PLUGIN:rbCat(\'|the_category|\');}}}'
            . '<textarea class="realblog_headline_field" name="realblog_headline"'
            . ' id="realblog_headline" rows="6" cols="60">'
            . XH_hsc(@$this->_headline) . '</textarea>';
    }

    /**
     * Renders the story (body).
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderStory()
    {
        global $plugin_tx;

        return '<h4>' . $plugin_tx['realblog']['story_label'] . '</h4>'
            . '<p><b>Script for copy &amp; paste:</b></p>'
            . '{{{PLUGIN:CommentsMembersOnly();}}}'
            . '<textarea class="realblog_story_field"'
             . ' name="realblog_story" id="realblog_story" rows="30" cols="80">'
             . XH_hsc(@$this->_story) . '</textarea>';
    }

    /**
     * Renders the submit buttons.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderSubmitButtons()
    {
        global $sn, $plugin_tx;

        return '<p style="text-align: center">'
            . tag(
                'input type="submit" name="save" value="'
                . $plugin_tx['realblog']['btn_' . $this->_getVerb()] . '"'
            )
            . '&nbsp;&nbsp;&nbsp;'
            . tag(
                'input type="button" name="cancel" value="'
                . $plugin_tx['realblog']['btn_cancel'] . '" onclick="'
                . 'location.href=&quot;' . $sn . '?&amp;realblog&amp;'
                . 'admin=plugin_main&amp;action=plugin_text&page='
                . $this->_retPage . '&quot;"'
            )
            . '</p>';
    }

    /**
     * Gets the current verb.
     *
     * @return string
     */
    private function _getVerb()
    {
        switch ($this->_action) {
        case 'add_realblog':
            return 'add';
        case 'modify_realblog':
            return 'modify';
        case 'delete_realblog':
            return 'delete';
        }
    }
}

/**
 * The confirmation views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
abstract class Realblog_ConfirmationView
{
    /**
     * The articles.
     *
     * @var array
     */
    protected $articles;

    /**
     * The title of the page.
     *
     * @var string
     */
    protected $title;

    /**
     * The label of the OK button.
     *
     * @var string
     */
    protected $buttonLabel;

    /**
     * Initializes a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->articles = Realblog_getPgParameter('realblogtopics');
    }

    /**
     * Renders the change status view.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        if (count($this->articles) > 0) {
            $html = $this->renderConfirmation();
        } else {
            $html = $this->renderNoSelectionInfo();
        }
        return $html;
    }

    /**
     * Renders the confirmation.
     *
     * @return string (X)HTML.
     */
    abstract protected function renderConfirmation();

    /**
     * Renders the hidden fields.
     *
     * @param string $do A do verb.
     *
     * @return string (X)HTML.
     *
     * @global string The number of the current page.
     */
    protected function renderHiddenFields($do)
    {
        global $page;

        $html = '';
        foreach ($this->articles as $value) {
            $html .= $this->renderHiddenField('realblogtopics[]', $value);
        }
        $html .= $this->renderHiddenField('page', $page)
            . $this->renderHiddenField('action', $do);
        return $html;
    }

    /**
     * Renders a hidden field.
     *
     * @param string $name  A field name.
     * @param string $value A field value.
     *
     * @return string (X)HTML.
     */
    protected function renderHiddenField($name, $value)
    {
        return tag(
            'input type="hidden" name="' . $name . '" value="' . $value . '"'
        );
    }

    /**
     * Renders the confirmation buttons
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string The number of the current page.
     */
    protected function renderConfirmationButtons()
    {
        global $sn, $plugin_tx, $page;

        $html = tag(
            'input type="submit" name="submit" value="'
            . $this->buttonLabel . '"'
        );
        $html .= '&nbsp;&nbsp;';
        $url = $sn . '?&amp;realblog&amp;admin=plugin_main&amp;action=plugin_text'
            . '&amp;page=' . $page;
        $html .= tag(
            'input type="button" name="cancel" value="'
            . $plugin_tx['realblog']['btn_cancel'] . '" onclick="'
            . 'location.href=&quot;' . $url . '&quot;"'
        );
        return $html;
    }

    /**
     * Renders the no selection information.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string The number of the current page.
     */
    protected function renderNoSelectionInfo()
    {
        global $sn, $plugin_tx, $page;

        return '<h1>Realblog &ndash; ' . $this->title . '</h1>'
            . '<form name="confirm" method="post" action="' . $sn
            . '?&amp;' . 'realblog' . '&amp;admin=plugin_main">'
            . '<table width="100%">'
            . '<tr><td class="realblog_confirm_info" align="center">'
            . $plugin_tx['realblog']['nothing_selected']
            . '</td></tr>'
            . '<tr><td class="realblog_confirm_button" align="center">'
            . tag(
                'input type="button" name="cancel" value="'
                . $plugin_tx['realblog']['btn_ok'] . '" onclick=\''
                . 'location.href="' . $sn . '?&amp;' . 'realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text'
                . '&amp;page=' . $page . '"\''
            )
            . '</td></tr>'
            . '</table></form>';
    }
}

/**
 * The delete views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_DeleteView extends Realblog_ConfirmationView
{
    /**
     * Initializes a new instance.
     *
     * @return void
     *
     * @global string The title of the page.
     * @global array  The localization of the plugins.
     */
    public function __construct()
    {
        global $title, $plugin_tx;

        parent::__construct();
        $this->buttonLabel = $plugin_tx['realblog']['btn_delete'];
        $title = $this->title = $plugin_tx['realblog']['tooltip_deleteall'];
    }

    /**
     * Renders the delete confirmation.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderConfirmation()
    {
        global $sn, $plugin_tx;

        $o = '<h1>Realblog &ndash; ' . $this->title . '</h1>';
        $o .= '<form name="confirm" method="post" action="' . $sn
            . '?&amp;realblog&amp;admin=plugin_main">'
            . $this->renderHiddenFields('do_delselected');
        $o .= '<table width="100%">';
        $o .= '<tr><td class="reablog_confirm_info" align="center">'
            . $plugin_tx['realblog']['confirm_deleteall']
            . '</td></tr><tr><td>&nbsp;</td></tr>';
        $o .= '<tr><td class="reablog_confirm_button" align="center">'
            . $this->renderConfirmationButtons()
            . '</td></tr>';
        $o .= '</table></form>';
        return $o;
    }
}

/**
 * The change status views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ChangeStatusView extends Realblog_ConfirmationView
{
    /**
     * Initializes a new instance.
     *
     * @return void
     *
     * @global string The title of the page.
     * @global array  The localization of the plugins.
     */
    public function __construct()
    {
        global $title, $plugin_tx;

        parent::__construct();
        $this->buttonLabel = $plugin_tx['realblog']['btn_ok'];
        $title = $this->title = $plugin_tx['realblog']['tooltip_changestatus'];
    }

    /**
     * Renders the change status confirmation.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderConfirmation()
    {
        global $sn, $plugin_tx;

        $html = '<h1>Realblog &ndash; ' . $this->title . '</h1>'
            . '<form name="confirm" method="post" action="' . $sn
            . '?&amp;' . 'realblog' . '&amp;admin=plugin_main">'
            . $this->renderHiddenFields('do_batchchangestatus')
            . '<table width="100%">'
            . '<tr><td width="100%" align="center">'
            . $this->_renderStatusSelect() . '</td></tr>'
            . '<tr><td class="realblog_confirm_info" align="center">'
            . $plugin_tx['realblog']['confirm_changestatus']
            . '</td></tr>'
            . '<tr><td>&nbsp;</td></tr>'
            . '<tr><td class="realblog_confirm_button" align="center">'
            . $this->renderConfirmationButtons() . '</td></tr>'
            . '</table></form>';
        return $html;
    }

    /**
     * Renders the status select.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderStatusSelect()
    {
        global $plugin_tx;

        $states = array(
            'entry_status', 'readyforpublishing', 'published', 'archived'
        );
        $html = '<select name="new_realblogstatus">';
        foreach ($states as $i => $state) {
            $value = $i - 1;
            $html .= '<option value="' . $value . '" ' . @$this->_status[$i] . '>'
                . $plugin_tx['realblog'][$state] . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

}

?>
