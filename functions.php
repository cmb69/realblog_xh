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
 */
function Realblog_blog($showSearch = false, $realBlogCat = 'all')
{
    global $title, $s, $h, $plugin_cf;

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
 */
function Realblog_archive($showSearch = false)
{
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
 * Makes a timestamp from a given date.
 *
 * The function also generates some internal settings, used in the datepicker.js
 * function. [Is that so?]
 *
 * @param int $tmpdate A timestamp.
 *
 * @return int
 *
 * @global array The localization of the plugins.
 *
 * @todo reuse Realblog_getCalendarDateFormat
 */
function Realblog_makeTimestampDates1($tmpdate = null)
{
    global $plugin_tx;

    $my_date_format = explode('/', $plugin_tx['realblog']['date_format']);
    if (count($my_date_format) > 1) {
        $date_separator = '/';
    } else {
        $my_date_format = explode('.', $plugin_tx['realblog']['date_format']);
        if (count($my_date_format) > 1) {
            $date_separator = '.';
        } else {
            $my_date_format = explode('-', $plugin_tx['realblog']['date_format']);
            if (count($my_date_format) > 1) {
                $date_separator = '-';
            }
        }
    }

    for ($aCounter = 0; $aCounter <= 2; $aCounter++) {
        switch ($my_date_format[$aCounter]) {
        case 'd':
            $dayposition = $aCounter;
            $my_detected_date_format[$dayposition] = $my_date_format[$aCounter];
            $cal_date_format[$dayposition] = 'DD';
            $regex[$dayposition] = '([0-9]{1,2})';
            break;
        case 'm':
            $monthposition = $aCounter;
            $my_detected_date_format[$monthposition] = $my_date_format[$aCounter];
            $cal_date_format[$monthposition] = 'MM';
            $regex[$monthposition] = '([0-9]{1,2})';
            break;
        case 'y':
            $yearposition = $aCounter;
            $my_detected_date_format[$yearposition] = $my_date_format[$aCounter];
            $cal_date_format[$yearposition] = 'YY';
            $regex[$yearposition] = '([0-9]{2})';
            break;
        case 'Y':
            $yearposition = $aCounter;
            $my_detected_date_format[$yearposition] = $my_date_format[$aCounter];
            $cal_date_format[$yearposition] = 'YYYY';
            $regex[$yearposition] = '([0-9]{4})';
            break;
        }
    }

    foreach ($my_detected_date_format as $key => $value) {
        if ($key < (count($my_detected_date_format) - 1)) {
            $date_format.=$value . $date_separator;
        } else {
            $date_format .= $value;
        }
    }
    foreach ($regex as $key => $value) {
        if ($key < (count($regex) - 1)) {
            $regex_format .= $value . $date_separator;
        } else {
            $regex_format .= $value;
        }
    }
    if ($tmpdate == null) {
        $tmpdate = date($plugin_tx['realblog']['date_format']);
    }
    // FIXME: remove ereg()
    if (ereg($regex_format, $tmpdate)) {
        // FIXME: means assignment???
        if ($date_separator = '.') {
            $dateArr = explode('.', $tmpdate);
        }
        if ($date_separator = '/') {
            $dateArr=explode('/', $tmpdate);
        }
        if ($date_separator = '-') {
            $dateArr = explode('-', $tmpdate);
        }
        $m = $dateArr[$monthposition];
        $d = $dateArr[$dayposition];
        $y = $dateArr[$yearposition];
    }
    $tmpdate = mktime(0, 0, 0, $m, $d, $y);
    return $tmpdate;
}

/**
 * Generates the RSS feed.
 *
 * @return void
 *
 * @global array  The paths of system files and folders.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 */
function Realblog_exportRssFeed()
{
    global $pth, $plugin_cf, $plugin_tx;

    if (strtolower($plugin_tx['realblog']['rss_enable']) == 'true') {
        $db = Realblog_connect();

        // FIXME: w+ ?
        if ($fp = fopen('./realblog_rss_feed.xml', 'w+')) {
            fputs(
                $fp,
                '<?xml version="1.0" encoding="'
                . strtolower($plugin_cf['realblog']['rss_encoding'])
                . '"?>' . "\n"
            );
            fputs(
                $fp,
                '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/"'
                . ' xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"'
                . ' xmlns:admin="http://webns.net/mvcb/"'
                . ' xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'
                . ' xmlns:content="http://purl.org/rss/1.0/modules/content/">'
                . "\n"
            );
            fputs($fp, '<channel>' . "\n");
            fputs(
                $fp,
                '<title>' . $plugin_tx['realblog']['rss_title'] . '</title>' . "\n"
            );
            fputs(
                $fp,
                '<link>' . $plugin_tx['realblog']['rss_page'] . '</link>' . "\n"
            );
            fputs(
                $fp,
                '<description>' . $plugin_tx['realblog']['rss_description']
                . '</description>' . "\n"
            );
            fputs(
                $fp,
                '<language>' . $plugin_tx['realblog']['rss_language'] . '</language>'
                . "\n"
            );
            fputs(
                $fp,
                '<copyright>' . $plugin_cf['realblog']['rss_copyright']
                . '</copyright>' . "\n"
            );
            fputs(
                $fp,
                '<managingEditor>' . $plugin_cf['realblog']['rss_editor']
                . '</managingEditor>' . "\n"
            );
            fputs(
                $fp,
                '<image>' . "\n"
            );
            fputs(
                $fp,
                '<title>' . $plugin_tx['realblog']['rss_title'] . '</title>' . "\n"
            );
            fputs(
                $fp,
                '<url>' . $plugin_cf['realblog']['rss_logo'] . '</url>' . "\n"
            );
            fputs(
                $fp,
                '<link>' . $plugin_tx['realblog']['rss_page'] . '</link>' . "\n"
            );
            fputs($fp, '<width>65</width>'. "\n");
            fputs($fp, '<height>35</height>' . "\n");
            fputs(
                $fp,
                '<description>' . $plugin_tx['realblog']['rss_description']
                . '</description>' . "\n"
            );
            fputs($fp, '</image>' . "\n");
            $compClause = new SimpleWhereClause(
                REALBLOG_RSSFEED, "=", "on", STRING_COMPARISON
            );
            $realbloglist = $db->selectWhere(
                'realblog.txt', $compClause, -1,
                array(
                    new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                    new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                )
            );
            // Show the RSS realblog items
            if (count($realbloglist) > 0) {
                foreach ($realbloglist as $index => $record) {
                    fputs($fp, '<item>' . "\n");
                    $title = '<title>'
                        . XH_hsc($record[REALBLOG_TITLE])
                        . '</title>' . "\n";
                    $url = Realblog_url(
                        $plugin_tx['realblog']["rss_page"],
                        $record['REALBLOG_TITLE'],
                        array(
                            'realblogID' => $record[REALBLOG_ID]
                        )
                    );
                    $link = '<link>' . XH_hsc($url) . '</link>' . "\n";
                    $description = '<description>'
                        . XH_hsc(evaluate_scripting($record[REALBLOG_HEADLINE]))
                        . '</description>' . "\n";
                    $pubDate = '<pubDate>' . date('r', $record[REALBLOG_DATE])
                        . '</pubDate>' . "\n";
                    fputs($fp, $title);
                    fputs($fp, $link);
                    fputs($fp, $description);
                    fputs($fp, $pubDate);
                    fputs($fp, '</item>' . "\n");
                }
            }
            fputs($fp, '</channel>' . "\n");
            fputs($fp, '</rss>' . "\n");
            fclose($fp);
        }
    }
}

/**
 * Returns a graphical hyperlink to the newsfeed file.
 *
 * @return string (X)HTML.
 *
 * @global array  The paths of system files and folders.
 * @global array  The localization of the plugins.
 */
function Realblog_feedLink()
{
    global $pth, $plugin_tx;

    return '<a href="./realblog_rss_feed.xml">'
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
 *
 * @staticvar string $cal_format The cache.
 */
function Realblog_getCalendarDateFormat()
{
    global $plugin_tx;
    static $cal_format;

    if (!isset($cal_format)) {
        $my_date_format1 = explode('/', $plugin_tx['realblog']['date_format']);
        if (count($my_date_format1) > 1) {
            $date_separator1 = '/';
        } else {
            $my_date_format1 = explode('.', $plugin_tx['realblog']['date_format']);
            if (count($my_date_format1) > 1) {
                $date_separator1 = '.';
            } else {
                $my_date_format1 = explode(
                    '-', $plugin_tx['realblog']['date_format']
                );
                if (count($my_date_format1) > 1) {
                    $date_separator1 = '-';
                }
            }
        }

        for ($aCounter1=0; $aCounter1 <= 2; $aCounter1++) {
            switch ($my_date_format1[$aCounter1]) {
            case 'd':
                $cal_date_format1[$aCounter1] = '%d';
                break;
            case 'm':
                $cal_date_format1[$aCounter1] = '%m';
                break;
            case 'y':
                $cal_date_format1[$aCounter1] = '%y';
                break;
            case 'Y':
                $cal_date_format1[$aCounter1] = '%Y';
                break;
            }
        }

        $cal_format = '';
        foreach ($cal_date_format1 as $key => $value) {
            $cal_format .= ($key < count($my_date_format1) - 1)
                ? $value . $date_separator1
                : $value;
        }
    }
    return $cal_format;
}

/**
 * Changes status to published when current date is within the publishing period.
 *
 * @return void
 */
function Realblog_autoPublish()
{
    $db = Realblog_connect();
    $today = strtotime('midnight');
    $compClause = new AndWhereClause(
        new SimpleWhereClause(REALBLOG_STATUS, '<=', 0, INTEGER_COMPARISON),
        new AndWhereClause(
            new SimpleWhereClause(REALBLOG_STARTDATE, '<=', $today),
            new SimpleWhereClause(REALBLOG_ENDDATE, '>=', $today)
        )
    );
    $records = $db->selectWhere('realblog.txt', $compClause, -1);

    foreach ($records as $key => $field) {
        $realblogitem[REALBLOG_ID] = $field[REALBLOG_ID];
        $realblogitem[REALBLOG_STATUS] = 1;
        $db->updateRowById('realblog.txt', REALBLOG_ID, $realblogitem);
    }
}

/**
 * Change realblog status from published to archived when publishing period
 * is ended.
 *
 * @return void
 *
 * @global array The configuration of the plugins.
 */
function Realblog_autoArchive()
{
    global $plugin_cf;

    $db = Realblog_connect();
    $compClause = new AndWhereClause(
        new SimpleWhereClause(REALBLOG_STATUS, '<=', 1),
        new SimpleWhereClause(REALBLOG_ENDDATE, '<', time(), INTEGER_COMPARISON)
    );

    $order = ($plugin_cf['realblog']['entries_order'] == 'desc')
        ? DESCENDING : ASCENDING;
    $records = $db->selectWhere(
        'realblog.txt', $compClause, -1,
        array(
            new OrderBy(REALBLOG_DATE, $order, INTEGER_COMPARISON),
            new OrderBy(REALBLOG_ID, $order, INTEGER_COMPARISON)
        )
    );

    foreach ($records as $key => $field) {
        $realblogitem[REALBLOG_ID] = $field[REALBLOG_ID];
        $realblogitem[REALBLOG_STATUS] = 2;
        $db->updateRowById('realblog.txt', REALBLOG_ID, $realblogitem);
    }
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
        $url .= '&' . urlencode(str_replace(' ', '-', $articleTitle));
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
            Realblog_makeTimestampDates1(
                Realblog_getPgParameter('realblog_from_date')
            )
        );
    }
    if (Realblog_getPgParameter('realblog_to_date') != '') {
        $compClauseDate2 = new SimpleWhereClause(
            REALBLOG_DATE, Realblog_getPgParameter('date_operator_2'),
            Realblog_makeTimestampDates1(Realblog_getPgParameter('realblog_to_date'))
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
 * FIXME: ???
 *
 * @param string $realblogID The article ID.
 * @param string $action     FIXME
 * @param int    $ret_page   FIXME
 *
 * @return string (X)HTML.
 *
 * @global string The page title.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 *
 * @todo Should $title by global'd; otherwise remove.
 */
function Realblog_form($realblogID = null, $action = null, $ret_page = 1)
{
    global $title, $plugin_cf, $plugin_tx;

    $db = Realblog_connect();
    if ($action == 'add_realblog') {
        $record = array(
            REALBLOG_ID => 0,
            REALBLOG_DATE => date($plugin_tx['realblog']['date_format']),
            REALBLOG_STARTDATE => date($plugin_tx['realblog']['date_format']),
            REALBLOG_ENDDATE => date($plugin_tx['realblog']['date_format']),
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
        $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);
        $realblog_id = $record[REALBLOG_ID];
        $record[REALBLOG_DATE] = date(
            $plugin_tx['realblog']['date_format'], $record[REALBLOG_DATE]
        );
        $record[REALBLOG_STARTDATE] = date(
            $plugin_tx['realblog']['date_format'], $record[REALBLOG_STARTDATE]
        );
        $record[REALBLOG_ENDDATE] = date(
            $plugin_tx['realblog']['date_format'], $record[REALBLOG_ENDDATE]
        );
        if ($action == 'modify_realblog') {
            $title = $plugin_tx['realblog']['tooltip_modify'] . ' [ID: '
                . $realblogID . ']';
        } elseif ($action == 'delete_realblog') {
            $title = $plugin_tx['realblog']['tooltip_delete'] . ' [ID: '
                . $realblogID . ']';
        }
    }
    $temp = new Realblog_ArticleAdminView($record, $action, $ret_page);
    return $temp->render();
}

/**
 * FIXME
 *
 * @param mixed $tmpdate FIXME
 *
 * @return int
 *
 * @global array The localization of the plugins.
 *
 * @todo Realblog_makeTimestampDates1() in index.php
 */
function Realblog_makeTimestampDates($tmpdate = null)
{
    global $plugin_tx;

    $my_date_format = explode('/', $plugin_tx['realblog']['date_format']);
    if (count($my_date_format) > 1) {
        $date_separator = '/';
    } else {
        $my_date_format = explode('.', $plugin_tx['realblog']['date_format']);
        if (count($my_date_format) > 1) {
            $date_separator = '.';
        } else {
            $my_date_format = explode('-', $plugin_tx['realblog']['date_format']);
            if (count($my_date_format) > 1) {
                $date_separator = '-';
            }
        }
    }

    for ($aCounter=0; $aCounter <= 2; $aCounter++) {
        switch ($my_date_format[$aCounter]) {
        case 'd':
            $dayposition = $aCounter;
            $my_detected_date_format[$dayposition] = $my_date_format[$aCounter];
            $cal_date_format[$dayposition] = 'DD';
            $regex[$dayposition] = '([0-9]{1,2})';
            break;
        case 'm':
            $monthposition = $aCounter;
            $my_detected_date_format[$monthposition] = $my_date_format[$aCounter];
            $cal_date_format[$monthposition] = 'MM';
            $regex[$monthposition] = '([0-9]{1,2})';
            break;
        case 'y':
            $yearposition = $aCounter;
            $my_detected_date_format[$yearposition] = $my_date_format[$aCounter];
            $cal_date_format[$yearposition] = 'YY';
            $regex[$yearposition] = '([0-9]{2})';
            break;
        case 'Y':
            $yearposition = $aCounter;
            $my_detected_date_format[$yearposition] = $my_date_format[$aCounter];
            $cal_date_format[$yearposition] = 'YYYY';
            $regex[$yearposition] = '([0-9]{4})';
            break;
        }
    }

    foreach ($my_detected_date_format as $key => $value) {
        if ($key < (count($my_detected_date_format) - 1)) {
            @$date_format .= $value . $date_separator;
        } else {
            $date_format.=$value;
        }
    }

    foreach ($regex as $key => $value) {
        if ($key < (count($regex) - 1)) {
            @$regex_format .= $value . $date_separator;
        } else {
            $regex_format .= $value;
        }
    }

    if ($tmpdate == null) {
        $tmpdate = date($plugin_tx['realblog']['date_format']);
    }

    if ($date_separator == '.') {
        $dateArr = explode('.', $tmpdate);
    }
    if ($date_separator == '/') {
        $dateArr = explode('/', $tmpdate);
    }
    if ($date_separator == '-') {
        $dateArr = explode('-', $tmpdate);
    }
    $m = $dateArr[$monthposition];
    $d = $dateArr[$dayposition];
    $y = $dateArr[$yearposition];

    $tmpdate = mktime(0, 0, 0, $m, $d, $y);
    return $tmpdate;
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
