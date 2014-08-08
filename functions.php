<?php

/**
 * Utility functions.
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
 * The fields of the article records.
 */
define('REALBLOG_ID', 0);
define('REALBLOG_DATE', 1);
define('REALBLOG_STARTDATE', 2);
define('REALBLOG_ENDDATE', 3);
define('REALBLOG_STATUS', 4);
define('REALBLOG_FRONTPAGE', 5); // seems to be unused
define('REALBLOG_TITLE', 6);
define('REALBLOG_HEADLINE', 7);
define('REALBLOG_STORY', 8);
define('REALBLOG_RSSFEED', 9);
define('REALBLOG_COMMENTS', 10);

/**
 * Displays the realblog's topic with status = published.
 *
 * @param array  $showSearch  Whether to show the searchform.
 * @param string $realBlogCat FIXME
 *
 * @return string (X)HTML.
 *
 * @global string The page title.
 * @global int    The current page index.
 * @global array  The headings of the pages.
 * @global array  The configuration of the plugins.
 * @global string The value of the page's meta description.
 */
function Realblog_blog($showSearch = false, $realBlogCat = 'all')
{
    global $title, $s, $h, $plugin_cf, $description;

    $realblogID = Realblog_getPgParameter('realblogID');
    $page = Realblog_getPage();
    $db = Realblog_connect();
    $t = '';
    if (!isset($realblogID)) {
        $compClause = new SimpleWhereClause(
            REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
        );

        if ($showSearch) {
            $temp = new Realblog_SearchFormView(
                Realblog_getYear()
            );
            $t .= $temp->render();
        }

        if (Realblog_getPgParameter('realblog_search')) {
            $compRealblogClause = new SimpleWhereClause(
                REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
            );
            $compClause = Realblog_searchClause();
            $articlesPerPage = PHP_INT_MAX;
            if (isset($compClause)) {
                $compClause = new AndWhereClause($compRealblogClause, $compClause);
            }
            $temp = ($plugin_cf['realblog']['entries_order'] == 'desc')
                ? DESCENDING : ASCENDING;
            $records = $db->selectWhere(
                'realblog.txt', $compClause, -1,
                array(
                    new OrderBy(REALBLOG_DATE, $temp, INTEGER_COMPARISON),
                    new OrderBy(REALBLOG_ID, $temp, INTEGER_COMPARISON)
                )
            );

            $numberOfSearchResults = $records;
            foreach ($numberOfSearchResults as $searchresults) {
                if (strstr($searchresults[8], '|' . $realBlogCat . '|')) {
                    $numberOfSearchResults[] = '';
                }
            }
            if ($realBlogCat != 'all') {
                $db_search_records = count($numberOfSearchResults)
                    - count($records);
            } else {
                $db_search_records = count($numberOfSearchResults);
            }

            $t .= Realblog_renderSearchResults('blog', $db_search_records);
        } else {
            $articlesPerPage = $plugin_cf['realblog']['entries_per_page'];
            if (empty($compClause)) {
                $compClause = $compRealblogClause;
            }

            $temp = ($plugin_cf['realblog']['entries_order'] == 'desc')
                ? DESCENDING : ASCENDING;
            $records = $db->selectWhere(
                'realblog.txt', $compClause, -1,
                array(
                    new OrderBy(REALBLOG_DATE, $temp, INTEGER_COMPARISON),
                    new OrderBy(REALBLOG_ID, $temp, INTEGER_COMPARISON)
                )
            );
        }

        $catRecords = array();
        foreach ($records as $catRecordsTemp) {
            if (strpos($catRecordsTemp[REALBLOG_HEADLINE], '|' . $realBlogCat . '|')
                || strpos($catRecordsTemp[REALBLOG_STORY], '|' . $realBlogCat . '|')
                || $realBlogCat == 'all'
            ) {
                $catRecords[] = $catRecordsTemp;
            }
        }

        $records = $catRecords;

        $temp = new Realblog_ArticlesView(
            $records, $realBlogCat, $articlesPerPage
        );
        $t .= $temp->render();
    } else {
        // Display the realblogitem for the given ID
        $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);
        if (count($record) > 0) {
            $description = Realblog_getDescription($record);
            $articleView = new Realblog_ArticleView($realblogID, $record, $page);
            $t .= $articleView->render();
            $title .= $h[$s] . " \xE2\x80\x93 " . $record[REALBLOG_TITLE];
        }
    }
    return $t;
}

/**
 * Displays the archived realblog topics.
 *
 * @param mixed $showSearch Whether to show the search form.
 *
 * @return string (X)HTML.
 *
 * @global string The value of the page's meta description.
 */
function Realblog_archive($showSearch = false)
{
    global $description;

    $realblogID = Realblog_getPgParameter('realblogID');
    $page = Realblog_getPage();

    $db = Realblog_connect();
    $t = '';
    if (!isset($realblogID)) {
        $compClause = new SimpleWhereClause(
            REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
        );

        if ($showSearch) {
            $temp = new Realblog_SearchFormView(
                Realblog_getYear()
            );
            $t .= $temp->render();
        }

        if (Realblog_getPgParameter('realblog_search')) {
            $compArchiveClause = new SimpleWhereClause(
                REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
            );
            $compClause = Realblog_searchClause();
            if (isset($compClause)) {
                $compClause = new AndWhereClause($compArchiveClause, $compClause);
            }
            $records = $db->selectWhere(
                'realblog.txt', $compClause, -1,
                array(
                    new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                    new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                )
            );
            $db_search_records = count($records);
            $t .= Realblog_renderSearchResults('archive', $db_search_records);
        } else {
            if (empty($compClause)) {
                $compClause=$compArchiveClause;
            }
            $records = $db->selectWhere(
                'realblog.txt', $compClause, -1,
                array(
                    new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                    new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                )
            );
        }

        $temp = new Realblog_ArchiveView($records);
        $t .= $temp->render();
    } else {
        // Display the realblogitem for the given ID
        $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);
        if (count($record) > 0) {
            $description = Realblog_getDescription($record);
            $articleView = new Realblog_ArticleView($realblogID, $record, $page);
            $t .= $articleView->render();
        }
    }
    return $t;
}

/**
 * Displays the realblog topics with a link to the blog page from the template.
 *
 * A page calling #cmsimple $output.=showrealblog();# must exist.
 * Options: realblog_page [required] : this is the page containing the
 *          showrealblog() function
 *
 * @param mixed $pageUrl A URL of a page where the blog is shown.
 *
 * @return string (X)HTML.
 *
 * @global array  The URLs of the pages.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 */
function Realblog_link($pageUrl)
{
    global $u, $plugin_cf, $plugin_tx;

    if (!in_array($pageUrl, $u)) {
        return '';
    }

    $db = Realblog_connect();

    if (!isset($id) || $id <= 0) {
        if ($plugin_cf['realblog']['links_visible'] > 0) {
            $t = '<p class="realbloglink">'
                . $plugin_tx['realblog']['links_visible_text'] . '</p>';
            // Select all published realblog items ordered by DATE
            // descending within the publishing range
            $compClause = new AndWhereClause(
                new SimpleWhereClause(
                    REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
                )
            );
            $realbloglist = $db->selectWhere(
                'realblog.txt', $compClause, -1,
                array(
                    new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                    new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                )
            );
            // Show the results
            $max_visible = $plugin_cf['realblog']['links_visible'];
            $realblog_counter = 0;
            if (count($realbloglist) > 0) {
                if ($max_visible <= 0 || empty($max_visible)) {
                    $max_visible = count($realbloglist);
                }
                $t .= "\n" . '<div class="realblog_tpl_show_box">' . "\n";
                foreach ($realbloglist as $index => $record) {
                    $realblog_counter++;
                    $t .= "\n" . '<div class="realblog_tpl_show_date">' . "\n"
                        .strftime(
                            $plugin_tx['realblog']['display_date_format'],
                            $record[REALBLOG_DATE]
                        )
                        . "\n" . '</div>';
                    $url = Realblog_url(
                        $pageUrl, $record[REALBLOG_TITLE], array(
                            'realblogID' => $record[REALBLOG_ID]
                        )
                    );
                    $t .= '<div class="realblog_tpl_show_title">'
                        . '<a href="' . XH_hsc($url) . '">'
                        . $record[REALBLOG_TITLE] .'</a></div>';
                    // Limit the number of visible realblog items (set in
                    // the configuration; empty=all realblog)
                    if ($plugin_cf['realblog']['links_visible'] > 0) {
                        if ($realblog_counter == $max_visible) {
                            break;
                        }
                    }
                }
                $t .= "\n" . '<div style="clear: both;"></div></div>' . "\n";
            } else {
                $t .= $plugin_tx['realblog']['no_topics'];
            }
            //$t.='</div>' . "\n";
        }
    }
    return $t;
}

/**
 * Delivers the RSS feed.
 *
 * @return void
 */
function Realblog_deliverFeed()
{
    header('Content-Type: application/rss+xml; charset=UTF-8');
    $db = Realblog_connect();
    $articles = $db->selectWhere(
        'realblog.txt',
        new SimpleWhereClause(
            REALBLOG_RSSFEED, "=", "on", STRING_COMPARISON
        ),
        -1,
        array(
            new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
            new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
        )
    );
    $view = new Realblog_RSSFeed($articles);
    echo $view->render();
    exit();
}

/**
 * Returns a graphical hyperlink to the RSS feed.
 *
 * @return string (X)HTML.
 *
 * @global array  The paths of system files and folders.
 * @global array  The localization of the plugins.
 */
function Realblog_feedLink()
{
    global $pth, $plugin_tx;

    return '<a href="./?realblog_feed=rss">'
        . tag(
            'img src="' . $pth['folder']['plugins'] . 'realblog/images/rss.png"'
            . ' alt="' . $plugin_tx['realblog']['rss_tooltip'] . '" title="'
            . $plugin_tx['realblog']['rss_tooltip'] . '" style="border: 0;"'
        )
        . '</a>';

}

/**
 * Connects to the flatfile database and returns the database object.
 *
 * @return Flatfile
 *
 * @global array The paths of system files and folders.
 *
 * @staticvar Flatfile $db The database object.
 */
function Realblog_connect()
{
    global $pth;
    static $db = null;

    if (!isset($db)) {
        include_once $pth['folder']['plugins'] . 'realblog/classes/flatfile.php';
        $db = new Flatfile();
        $db->datadir = $pth['folder']['content'] . 'realblog/';
    }
    return $db;
}

/**
 * Returns the date format for the datepicker.js script.
 *
 * @return string
 *
 * @global array The localization of the plugins.
 */
function Realblog_getCalendarDateFormat()
{
    global $plugin_tx;

    return preg_replace(
        '/(d|m|y|Y)/', '%$1', $plugin_tx['realblog']['date_format']
    );
}

/**
 * Changes status to published when publishing date is reached.
 *
 * @return void
 */
function Realblog_autoPublish()
{
    Realblog_changeStatus(REALBLOG_STARTDATE, 1);
}

/**
 * Changes status to archived when archive date is reached.
 *
 * @return void
 */
function Realblog_autoArchive()
{
    Realblog_changeStatus(REALBLOG_ENDDATE, 2);
}

/**
 * Changes the status according to the value of a certain field.
 *
 * @param int $field  A field number.
 * @param int $status A status code.
 *
 * @return void
 */
function Realblog_changeStatus($field, $status)
{
    $db = Realblog_connect();
    $records = $db->selectWhere(
        'realblog.txt',
        new AndWhereClause(
            new SimpleWhereClause(REALBLOG_STATUS, '<', $status, INTEGER_COMPARISON),
            new SimpleWhereClause(
                $field, '<=', strtotime('midnight'), INTEGER_COMPARISON
            )
        )
    );
    foreach ($records as $record) {
        $db->updateRowById(
            'realblog.txt', REALBLOG_ID,
            array(
                REALBLOG_ID => $record[REALBLOG_ID],
                REALBLOG_STATUS => $status
            )
        );
    }
}

/**
 * Returns the meta description for an article.
 *
 * @param array $article An article record.
 *
 * @return string
 */
function Realblog_getDescription($article)
{
    return utf8_substr(
        html_entity_decode(
            strip_tags($article[REALBLOG_HEADLINE]), ENT_COMPAT, 'UTF-8'
        ),
        0, 150
    );
}

/**
 * Parses the $arguments string and returns a map of names to values.
 *
 * @param string $arguments An arguments string ('name1=value1,name2=value2').
 *
 * @return array
 */
function Realblog_getArguments($arguments)
{
    $result = array();
    $arguments = explode(',', $arguments);
    foreach ($arguments as $argument) {
        $pair = explode('=', $argument);
        if (count($pair) == 2) {
            $result[$pair[0]] = $pair[1];
        }
    }
    return $result;
}

/**
 * Returns the value of a POST or GET parameter; <var>null</var> if not set.
 *
 * @param string $name A parameter name.
 *
 * @return string
 */
function Realblog_getPgParameter($name)
{
    if (isset($_POST[$name])) {
        return $_POST[$name];
    } elseif (isset($_GET[$name])) {
        return $_GET[$name];
    } else {
        return null;
    }
}

/**
 * Returns the requested page number, and stores it in a cookie.
 *
 * @return int
 */
function Realblog_getPage()
{
    if (isset($_GET['realblog_page'])) {
        $page = (int) $_GET['realblog_page'];
        $_COOKIE['realblog_page'] = $page;
        setcookie('realblog_page', $page, 0, CMSIMPLE_ROOT);
    } elseif (isset($_COOKIE['realblog_page'])) {
        $page = (int) $_COOKIE['realblog_page'];
    } else {
        $page = 1;
    }
    return $page;
}

/**
 * Returns the requested year, and stores it in a cookie.
 *
 * @return int
 */
function Realblog_getYear()
{
    if (isset($_GET['realblog_year'])) {
        $year = (int) $_GET['realblog_year'];
        $_COOKIE['realblog_year'] = $year;
        setcookie('realblog_year', $year, 0, CMSIMPLE_ROOT);
    } elseif (isset($_COOKIE['realblog_year'])) {
        $year = (int) $_COOKIE['realblog_year'];
    } else {
        $year = date('Y');
    }
    return $year;
}

/**
 * Returns a requested filter, and stores it in a cookie.
 *
 * @param int $num A filter number (1-3).
 *
 * @return bool
 */
function Realblog_getFilter($num)
{
    if (isset($_POST["realblog_filter$num"])) {
        $filter = ($_POST["realblog_filter$num"] == 'on');
        $_COOKIE["realblog_filter$num"] = $filter ? 'on' : '';
        setcookie("realblog_filter$num", $filter ? 'on' : '', 0, CMSIMPLE_ROOT);
    } elseif (isset($_COOKIE["realblog_filter$num"])) {
        $filter = ($_COOKIE["realblog_filter$num"] == 'on');
    } else {
        $filter = false;
    }
    return $filter;
}

/**
 * Constructs a front-end URL.
 *
 * @param string $pageUrl      A page URL.
 * @param string $articleTitle An article title.
 * @param array  $params       A map of names -> values.
 *
 * @return string
 */
function Realblog_url($pageUrl, $articleTitle = null, $params = array())
{
    global $sn;

    $replacePairs = array(
        //'realblogID' => 'id',
        //'realblog_page' => 'page'
    );
    $url = $sn . '?' . $pageUrl;
    if (isset($articleTitle)) {
        $url .= '&' . uenc($articleTitle);
    }
    ksort($params);
    foreach ($params as $name => $value) {
        $url .= '&' . strtr($name, $replacePairs) . '=' . $value;
    }
    return $url;
}

/**
 * Returns the search clause.
 *
 * @return CompositeWhereClause
 *
 * @todo realblog_from_date and realblog_to_date are unused!
 */
function Realblog_searchClause()
{
    if (Realblog_getPgParameter('realblog_from_date') != '') {
        $compClauseDate1 = new SimpleWhereClause(
            REALBLOG_DATE, Realblog_getPgParameter('date_operator_1'),
            Realblog_stringToTime(Realblog_getPgParameter('realblog_from_date'))
        );
    }
    if (Realblog_getPgParameter('realblog_to_date') != '') {
        $compClauseDate2 = new SimpleWhereClause(
            REALBLOG_DATE, Realblog_getPgParameter('date_operator_2'),
            Realblog_stringToTime(Realblog_getPgParameter('realblog_to_date'))
        );
    }
    if (Realblog_getPgParameter('realblog_title') != '') {
        $compClauseTitle = new LikeWhereClause(
            REALBLOG_TITLE, Realblog_getPgParameter('realblog_title'),
            2 // TODO: Realblog_getPgParameter('title_operator')
        );
    }
    if (Realblog_getPgParameter('realblog_story') != '') {
        $compClauseStory = new LikeWhereClause(
            REALBLOG_STORY, Realblog_getPgParameter('realblog_story'),
            2 // TODO: Realblog_getPgParameter('story_operator')
        );
    }

    $code = (int) !empty($compClauseDate1) << 3
        | (int) !empty($compClauseDate2) << 2
        | (int) !empty($compClauseTitle) << 1
        | (int) !empty($compClauseStory);
    switch ($code) {
    case 0:
        $compClause = null;
        break;
    case 1:
        $compClause = $compClauseStory;
        break;
    case 2:
        $compClause = $compClauseTitle;
        break;
    case 3:
        switch (Realblog_getPgParameter('realblog_search')) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClauseTitle, $compClauseStory
            );
            break;
        default:
            $compClause = new OrWhereClause(
                $compClauseTitle, $compClauseStory
            );
        }
        break;
    case 4:
        $compClause = $compClauseDate2;
        break;
    case 5:
        switch (Realblog_getPgParameter('realblog_search')) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClauseDate2, $compClauseStory
            );
            break;
        default:
            $compClause = new OrWhereClause(
                $compClauseDate2, $compClauseStory
            );
        }
        break;
    case 6:
        switch (Realblog_getPgParameter('operator_1')) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClauseDate2, $compClauseTitle
            );
            break;
        default:
            $compClause = new OrWhereClause(
                $compClauseDate2, $compClauseTitle
            );
        }
        break;
    case 7:
        $compClause = $compClauseDate2;
        switch (Realblog_getPgParameter('operator_1')) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseTitle);
            break;
        default:
            $compClause = new OrWhereClause($compClause, $compClauseTitle);
        }
        switch (Realblog_getPgParameter('realblog_search')) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseStory);
            break;
        default:
            $compClause = new OrWhereClause($compClause, $compClauseStory);
        }
        break;
    case 8:
        $compClause = $compClauseDate1;
        break;
    case 9:
        switch (Realblog_getPgParameter('realblog_search')) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClauseDate1, $compClauseStory
            );
            break;
        default:
            $compClause = new OrWhereClause(
                $compClauseDate1, $compClauseStory
            );
        }
        break;
    case 10:
        switch (Realblog_getPgParameter('operator_1')) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClauseDate1, $compClauseTitle
            );
            break;
        default:
            $compClause = new OrWhereClause(
                $compClauseDate1, $compClauseTitle
            );
        }
        break;
    case 11:
        $compClause = $compClauseDate1;
        switch (Realblog_getPgParameter('operator_1')) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClause, $compClauseTitle
            );
            break;
        default:
            $compClause = new OrWhereClause(
                $compClause, $compClauseTitle
            );
        }
        switch (Realblog_getPgParameter('realblog_search')) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseStory);
            break;
        default:
            $compClause = new OrWhereClause($compClause, $compClauseStory);
        }
        break;
    case 12:
        $compClause = new AndWhereClause($compClauseDate1, $compClauseDate2);
        break;
    case 13:
        switch (Realblog_getPgParameter('realblog_search')) {
        case 'AND':
            $compClause = new AndWhereClause(
                new AndWhereClause($compClauseDate1, $compClauseDate2),
                $compClauseStory
            );
            break;
        default:
            $compClause = new OrWhereClause(
                new AndWhereClause($compClauseDate1, $compClauseDate2),
                $compClauseStory
            );
        }
        break;
    case 14:
        switch (Realblog_getPgParameter('operator_1')) {
        case 'AND':
            $compClause = new AndWhereClause(
                new AndWhereClause($compClauseDate1, $compClauseDate2),
                $compClauseTitle
            );
            break;
        default:
            $compClause = new OrWhereClause(
                new AndWhereClause($compClauseDate1, $compClauseDate2),
                $compClauseTitle
            );
        }
        break;
    case 15:
        $compClause = new AndWhereClause($compClauseDate1, $compClauseDate2);
        switch (Realblog_getPgParameter('operator_1')) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseTitle);
            break;
        default:
            $compClause = new OrWhereClause($compClause, $compClauseTitle);
        }
        switch (Realblog_getPgParameter('realblog_search')) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseStory);
            break;
        default:
            $compClause = new OrWhereClause($compClause, $compClauseStory);
        }
        break;
    }
    return $compClause;
}

/**
 * Renders the search results.
 *
 * @param string $what  Which search results ('blog' or 'archive').
 * @param string $count The number of hits.
 *
 * @return string (X)HTML.
 *
 * @global string The URL of the current page.
 * @global array  The localization of the plugins.
 */
function Realblog_renderSearchResults($what, $count)
{
    global $su, $plugin_tx;

    $key = ($what == 'archive') ? 'back_to_archive' : 'search_show_all';
    $title = Realblog_getPgParameter('realblog_title');
    $story = Realblog_getPgParameter('realblog_story');
    $operator = Realblog_getPgParameter('realblog_search');
    $operator = ($operator == 'AND')
        ? $plugin_tx['realblog']['search_and']
        : $plugin_tx['realblog']['search_or'];
    $words = '"' . $title . '" ' . $operator . ' "' . $story . '"';
    return '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>'
        . XH_hsc($words) . '</b></p>'
        . '<p>' . $plugin_tx['realblog']['search_result'] . '<b> '
        . $count . '</b></p>'
        . '<p><a href="' . XH_hsc(Realblog_url($su)) . '"><b>'
        . $plugin_tx['realblog'][$key] . '</b></a></p>';
}

/**
 * Writes the required references to the head element.
 *
 * @return void
 *
 * @global array The paths of system files and folders.
 * @global string The current language.
 * @global string The (X)HTML fragment to insert in the head element.
 *
 * @todo Check files for existance.
 */
function Realblog_useCalendar()
{
    global $pth, $sl, $hjs;

    $hjs .= tag(
        'link rel="stylesheet" type="text/css" media="all" href="'
        . $pth['folder']['plugins'] . 'realblog/jscalendar/calendar-system.css"'
    );
    $hjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
        . 'realblog/jscalendar/calendar.js"></script>';
    $filename = $pth['folder']['plugins'] . 'realblog/jscalendar/lang/calendar-'
        . $sl . '.js';
    if (file_exists($filename)) {
        $hjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
            . 'realblog/jscalendar/lang/calendar-' . $sl . '.js"></script>';
    } else {
        $hjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
            . 'realblog/jscalendar/lang/calendar-en.js"></script>';
    }
    $hjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
        . 'realblog/jscalendar/calendar-setup.js"></script>';
}

/**
 * Renders the article form.
 *
 * @param string $id     An article ID.
 * @param string $action An action.
 *
 * @return string (X)HTML.
 *
 * @global string The page title.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 */
function Realblog_form($id, $action)
{
    global $title, $plugin_cf, $plugin_tx;

    $db = Realblog_connect();
    if ($action == 'add_realblog') {
        $record = array(
            REALBLOG_ID => 0,
            REALBLOG_DATE => date($plugin_tx['realblog']['date_format']),
            REALBLOG_STARTDATE => date($plugin_tx['realblog']['date_format']),
            REALBLOG_ENDDATE => date(
                $plugin_tx['realblog']['date_format'], 2147483647
            ),
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
        $record[REALBLOG_DATE] = date(
            $plugin_tx['realblog']['date_format'], $record[REALBLOG_DATE]
        );
        $record[REALBLOG_STARTDATE] = date(
            $plugin_tx['realblog']['date_format'], (int) $record[REALBLOG_STARTDATE]
        );
        $record[REALBLOG_ENDDATE] = date(
            $plugin_tx['realblog']['date_format'], $record[REALBLOG_ENDDATE]
        );
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
 * Parses a date string and returns a timestamp.
 *
 * @param mixed $date A date string.
 *
 * @return int
 *
 * @global array The localization of the plugins.
 */
function Realblog_stringToTime($date)
{
    global $plugin_tx;

    if (strpos($plugin_tx['realblog']['date_format'], '/') !== false) {
        $separator = '/';
    } elseif (strpos($plugin_tx['realblog']['date_format'], '.') !== false) {
        $separator = '.';
    } elseif (strpos($plugin_tx['realblog']['date_format'], '-') !== false) {
        $separator = '-';
    }
    $parts = explode($separator, $plugin_tx['realblog']['date_format']);
    for ($i = 0; $i < count($parts); $i++) {
        switch ($parts[$i]) {
        case 'd':
            $day = $i;
            break;
        case 'm':
            $month = $i;
            break;
        case 'y':
        case 'Y':
            $year = $i;
            break;
        }
    }
    $parts = explode($separator, $date);
    return mktime(
        0, 0, 0, $parts[$month], $parts[$day], $parts[$year]
    );
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
function Realblog_dbconfirm($title, $info, $page)
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

?>
