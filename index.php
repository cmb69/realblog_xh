<?php

/**
 * The front-end functionality.
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

if (!function_exists('sv')
    || preg_match('#/plugins/realblog/index.php#i', $_SERVER['SCRIPT_NAME'])
) {
    die('no direct access');
}

//////////////////////////////////////////////// HISTORIC LICENSE SECTION START
/*
************************************
RealBlog plugin for CMSimple
RealBlog v2.8
released 2014-05-11
Gert Ebersbach - http://www.ge-webdesign.de
------------------------------------
Based on:  AdvancedNews from Jan Kanters - http://www.jat-at-home.be/
Version :  V 1.0.5 GPL
------------------------------------
Credits :  - flatfile database class Copyright 2005 Luke Plant
             <L.Plant.98@cantab.net>
           - FCKEditor (older versions) and TinyMCE
           - Date Picker (jscalendar) by Copyright (c) Dynarch.com
License :  GNU General Public License, version 2 or later of your choice
************************************

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with
this program; if not, see <http://www.gnu.org/licenses>.
*/
////////////////////////////////////////////////// HISTORIC LICENSE SECTION END

require_once $pth['folder']['plugin'] . 'functions.php';
require_once $pth['folder']['plugin_classes'] . 'Presentation.php';
/**
 * Backward compatibility.
 */
require_once $pth['folder']['plugin'] . 'compat.php';

if (session_id() == '') {
    session_start();
}

/**
 * The plugin version.
 */
define('REALBLOG_VERSION', '@REALBLOG_VERSION@');

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

/*
 * This routine does some automatic realblog status updating
 * it changes the realblog status automatically from :
 *   - ready to publish -> published; when current date is in between start
 *     & end date
 *   - published -> archived; when current date > end date
 * and also generates an up-to-date RSS newsfeed file
*/

$rss_path='./';
if (!is_writeable($rss_path . 'realblog_rss_feed.xml') && $adm) {
    $o.= '<div class="cmsimplecore_warning" style="text-align: center;">'
        . '<b>RealBlog:</b> RSS file "./realblog_rss_feed.xml" not writable.'
        . '</div>';
}

// FIXME: move to admin.php
if (!$adm) {
    Realblog_exportRssFeed();
    $plugin = basename(dirname(__FILE__), "/");

    // set general variables for the plugin
    $plugin_images_folder = $pth['folder']['plugins'] . $plugin . "/images/";
    $plugin_include_folder = $pth['folder']['plugins'] . $plugin . "/include/";

    $db_path = $pth['folder']['content'] . 'realblog/';

    $db_name = "realblog.txt";

    $db = Realblog_connect();

    // Change realblog status from ready for publishing to published when
    // current date is within the publishing period
    $compClause = null;

    if (strtolower($plugin_cf[$plugin]['auto_publish']) == 'true') {
        Realblog_autoPublish();
    }
    if ($plugin_cf['realblog']['auto_archive'] == 'true') {
        Realblog_autoArchive();
    }
    $hjs .= tag(
        'link rel="alternate" type="application/rss+xml" title="'
        . sitename(). '" href="./realblog_rss_feed.xml"'
    ) . "\n";
}

/**
 * Displays the realblog's topic with status = published.
 *
 * @param array  $options     FIXME
 * @param string $realBlogCat FIXME
 *
 * @return string (X)HTML.
 *
 * @global string Whether we're in admin mode.
 * @global array  The paths of system files and folders.
 * @global string The script name.
 * @global string The contents of the title element.
 * @global array  The localization of the plugins.
 * @global array  The configuration of the plugins.
 * @global array  The URLs of the pages.
 * @global int    The current page index.
 * @global array  The contents of the pages.
 * @global string The current language.
 * @global string The current special functionality.
 * @global array  The localization of the core.
 * @global mixed  FIXME
 * @global string The (X)HTML fragment to insert into the head element.
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global string The requested page URL.
 */
function Realblog_blog($options = null, $realBlogCat = 'all')
{
    global $adm, $pth, $sn, $title, $plugin_tx, $plugin_cf, $u, $s, $c, $sl, $f,
        $tx, $cal_format, $hjs, $realblogID, $commentschecked, $id, $from_page,
        $page, $realblog_page, $su;

    // get plugin name
    $plugin = basename(dirname(__FILE__), '/');

    $includesearch = 'false';
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['showsearch'])) {
        $argument = strtolower($arguments['showsearch']);
        if (in_array($argument, array('true', 'false', '1', '0'))) {
            switch ($argument) {
            case '0':
                $includesearch = 'false';
                break;
            case '1':
                $includesearch = 'true';
                break;
            default:
                $includesearch = $argument;
            }
        }
    }

    $realblogID = Realblog_getPgParameter('realblogID');
    $page = Realblog_getPgParameter('page');
    $realblogaction = Realblog_getPgParameter('realblogaction');
    $realblogYear = Realblog_getPgParameter('realblogYear');
    $compClause = Realblog_getPgParameter('compClause');

    // set general variables for the plugin
    $plugin_images_folder = $pth['folder']['plugins'] . $plugin . '/images/';

    // Show / hide search block
    $hjs .= "\n" . <<<EOT
<script type="text/javascript">
function realblog_showSearch() {
    if (document.getElementById("searchblock").style.display == "none") {
        var mytitle = "{$plugin_tx[$plugin]['tooltip_hidesearch']}";
        document.getElementById("btn_img").title = mytitle;
        document.getElementById("btn_img").src =
                "{$plugin_images_folder}btn_collapse.gif";
        document.getElementById("searchblock").style.display = "block";
    } else {
        var mytitle = "{$plugin_tx[$plugin]['tooltip_showsearch']}";
        document.getElementById("btn_img").title = mytitle;
        document.getElementById("btn_img").src
                = "{$plugin_images_folder}btn_expand.gif";
        document.getElementById("searchblock").style.display = "none";
    }
}
</script>'
EOT;

    $db = Realblog_connect();

    if ($realblogaction != 'view') {
        $compClause = new SimpleWhereClause(
            REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
        );

        $cal_format = Realblog_getCalendarDateFormat();

        if ($includesearch == 'true') {
            $t = Realblog_renderSearchForm($realblogYear);
        }

        if ($realblogaction == 'search') {
            $compRealblogClause = new SimpleWhereClause(
                REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
            );
            $compClause = Realblog_searchClause();
        }

        if ($realblogaction == "search") {
            $plugin_cf['realblog']['entries_per_page'] = '0';
            //$compClause=serialize($compClause);
            if (isset($compClause)) {
                $compClause = new AndWhereClause($compRealblogClause, $compClause);
            } else {
                unset($realblogaction);
            }
            $temp = $plugin_cf['realblog']['entries_order'] == 'desc'
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

            $t .= '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>"'
                . $_REQUEST['realblog_story'] . '"</b></p>';
            $t .= '<p>' . $plugin_tx['realblog']['search_result'] . '<b> '
                . $db_search_records . '</b></p>';
            $t .= '<p><a href="'
                . preg_replace('/\&.*\z/', '', $_SERVER['REQUEST_URI']) . '"><b>'
                . $plugin_tx['realblog']['search_show_all'] . '</b></a></p>'
                . tag('br');
        } else {
            if (empty($compClause)) {
                $compClause = $compRealblogClause;
            }

            $temp = $plugin_cf['realblog']['entries_order'] == 'desc'
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
            if (strpos($catRecordsTemp[7], '|' . $realBlogCat . '|')
                || strpos($catRecordsTemp[8], '|' . $realBlogCat . '|')
                || $realBlogCat == 'all'
            ) {
                $catRecords[] = $catRecordsTemp;
            }
        }

        $records = $catRecords;

        $temp = new Realblog_ArticlesView(
            $records, $realBlogCat, $realblogaction
        );
        $t = $temp->render();
    } else {
        // Display the realblogitem for the given ID
        $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);

        // Set the return page, based on the caling page
        if ($from_page == '' || empty($from_page)) {
            $from_page = 1;
        }
        $return_page = ($from_page == $u[$s]) ? $u[$s] : $from_page;

        if (count($record) > 0) {
            $articleView = new Realblog_ArticleView($realblogID, $record, $page);
            $t = $articleView->render();
            // FIXME: see cmsimpleforum.com
            $title .= locator() . ' - ' . $record[REALBLOG_TITLE];
        }
    }
    // FIXME?
    $c[$s] = '';
    // FIXME?
    unset($realblogaction);
    unset($compClause);
    return $t;
}

/**
 * Displays the archived realblog topics.
 *
 * @param mixed $options FIXME
 *
 * @return string (X)HTML.
 *
 * @global string Whether we're in admin mode.
 * @global array  The paths of system files and folders.
 * @global string The script name.
 * @global string The contents of the title element.
 * @global array  The localization of the plugins.
 * @global array  The configuration of the plugins.
 * @global array  The URLs of the pages.
 * @global int    The current page index.
 * @global array  The contents of the pages.
 * @global string The current language.
 * @global string The current special functionality.
 * @global array  The localization of the core.
 * @global mixed  FIXME
 * @global string The (X)HTML fragment to insert into the head element.
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global mixed  FIXME
 * @global string The URL of the requested page.
 * @global mixed  FIXME
 */
function Realblog_archive($options = null)
{
    global $adm, $pth, $sn, $title, $plugin_tx, $plugin_cf, $u, $s, $c, $sl, $f,
        $tx, $cal_format, $hjs, $realblogID, $commentschecked, $id, $from_page,
        $page, $su, $realblogYear;

    $plugin = basename(dirname(__FILE__), '/');

    $layout = 'archive';
    $includesearch = 'false';
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['showsearch'])) {
        $argument = strtolower($arguments['showsearch']);
        if (in_array($argument, array('true', 'false', '1', '0'))) {
            switch ($argument) {
            case '0':
                $includesearch = 'false';
                break;
            case '1':
                $includesearch = 'true';
                break;
            default:
                $includesearch = $argument;
            }
        }
    }

    $realblogID = Realblog_getPgParameter('realblogID');
    $page = Realblog_getPgParameter('page');
    $realblogaction = Realblog_getPgParameter('realblogaction');
    $realblogYear = Realblog_getPgParameter('realblogYear');
    $compClause = Realblog_getPgParameter('compClause');

    $plugin_images_folder = $pth['folder']['plugins'] . $plugin . '/images/';

    // Show / hide search block
    $hjs .= "\n" . <<<EOT
<script type="text/javascript">
function realblog_showSearch() {
    if (document.getElementById("searchblock").style.display == "none") {
        var mytitle = "{$plugin_tx[$plugin]['tooltip_hidesearch']}";
        document.getElementById("btn_img").title = mytitle;
        document.getElementById("btn_img").src =
                "{$plugin_images_folder}btn_collapse.gif";
        document.getElementById("searchblock").style.display = "block";
    } else {
        var mytitle = "{$plugin_tx[$plugin]['tooltip_showsearch']}";
        document.getElementById("btn_img").title = mytitle;
        document.getElementById("btn_img").src =
                "{$plugin_images_folder}btn_expand.gif";
        document.getElementById("searchblock").style.display = "none";
    }
}
</script>
EOT;

    $db = Realblog_connect();

    if ($realblogaction != 'view') {
        $compClause = new SimpleWhereClause(
            REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
        );

        $cal_format = Realblog_getCalendarDateFormat();

        if ($includesearch == 'true') {
            $t = Realblog_renderSearchForm($realblogYear);
        }

        if ($realblogaction == 'search') {
            $compArchiveClause = new SimpleWhereClause(
                REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
            );
            $compClause = Realblog_searchClause();
        }

        if ($realblogaction == 'search') {
            //$compClause=serialize($compClause);
            if (isset($compClause)) {
                $compClause = new AndWhereClause($compArchiveClause, $compClause);
            } else {
                unset($realblogaction);
            }
            $records = $db->selectWhere(
                'realblog.txt', $compClause, -1,
                array(
                    new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                    new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                )
            );
            $db_search_records = count($records);
            $t .= '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>"'
                . $_REQUEST['realblog_story'] . '"</b></p>';
            $t .= '<p>' . $plugin_tx['realblog']['search_result'] . '<b> '
                . $db_search_records . '</b></p>';
            $t .= '<p><a href="'
                . preg_replace('/\&.*\z/', '', $_SERVER['REQUEST_URI']) . '"><b>'
                . $plugin_tx['realblog']['back_to_archive'] . '</b></a></p>';
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

        switch (strtolower($layout)) {
        case 'archive':
            $realblog_topics_total = count($records);
            $filter_total = 0;
            if ($realblogaction != 'search') {
                $currentYear = date('Y');
                if (!isset($realblogYear) || $realblogYear <= 0
                    || $realblogYear >= $currentYear || empty($realblogYear)
                ) {
                    $realblogYear = $currentYear;
                    $currentMonth = date('n');
                } else {
                    $currentMonth = 12;
                }
                $_SESSION['realblogYear'] = $realblogYear;
                $next = ($realblogYear < $currentYear)
                    ? ($realblogYear + 1) : $currentYear;
                $back = $realblogYear - 1;
                $t .= "\n" . '<div>&nbsp;</div>' . "\n";
                $t .= "\n" . '<div class="realblog_table_paging">' . "\n"
                    . '<a href="' . $sn . '?' . $u[$s] . '&amp;realblogYear='
                    . $back . '" title="'
                    . $plugin_tx[$plugin]['tooltip_previousyear'] . '">'
                    //. tag(
                    //    'img src="' . $plugin_images_folder . 'btn_previous.gif"'
                    //    . ' alt="previous_img"'
                    //)
                    . '&#9664;</a>&nbsp;&nbsp;';
                $t .= '<b>' . $plugin_tx[$plugin]['archive_year']
                    . $realblogYear . '</b>';
                $t .= '&nbsp;&nbsp;<a href="' . $sn . '?' . $u[$s]
                    . '&amp;realblogYear=' . $next . '" title="'
                    . $plugin_tx[$plugin]['tooltip_nextyear'] . '">'
                    //. tag(
                    //    'img src="' . $plugin_images_folder . 'next.png"'
                    //    . ' alt="next_img"'
                    //)
                    . '&#9654;</a>';
                $t .= '</div>';
                $t .= "\n" . '<div>&nbsp;</div>' . "\n";
                $startmonth = mktime(0, 0, 0, 1, 1, $realblogYear);
                $endmonth = mktime(0, 0, 0, 12, 1, $realblogYear);
                $compClause = new AndWhereClause(
                    new AndWhereClause(
                        new SimpleWhereClause(
                            REALBLOG_DATE, '>=', $startmonth, INTEGER_COMPARISON
                        ),
                        new SimpleWhereClause(
                            REALBLOG_DATE, '<=', $endmonth, INTEGER_COMPARISON
                        )
                    ),
                    new SimpleWhereClause(
                        REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
                    )
                );
                $generalrealbloglist = $db->selectWhere(
                    'realblog.txt', $compClause, -1,
                    array(
                        new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                        new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                    )
                );

                for ($month = $currentMonth; $month >= 1; $month--) {
                    $startmonth = mktime(0, 0, 0, $month, 1, $realblogYear);
                    $endmonth = mktime(0, 0, 0, $month + 1, 1, $realblogYear);
                    $compClause = new AndWhereClause(
                        new SimpleWhereClause(
                            REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
                        ),
                        new AndWhereClause(
                            new SimpleWhereClause(
                                REALBLOG_DATE, '>=', $startmonth, INTEGER_COMPARISON
                            ),
                            new SimpleWhereClause(
                                REALBLOG_DATE, '<', $endmonth, INTEGER_COMPARISON
                            ),
                            new SimpleWhereClause(
                                REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
                            )
                        )
                    );
                    $realbloglist = $db->selectWhere(
                        'realblog.txt', $compClause, -1,
                        array(
                            new OrderBy(
                                REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON
                            ),
                            new OrderBy(
                                REALBLOG_ID, DESCENDING, INTEGER_COMPARISON
                            )
                        )
                    );
                    $monthString = strftime(
                        '%B %Y', mktime(0, 0, 0, $month, 1, $realblogYear)
                    );
                    $month_search = explode(
                        ',', $plugin_tx['realblog']['date_month_search']
                    );
                    $month_replace = explode(
                        ',', $plugin_tx['realblog']['date_month_replace']
                    );
                    $monthString = str_ireplace(
                        $month_search, $month_replace, $monthString
                    );
                    if (count($realbloglist) > 0) {
                        $t .= "\n" . '<h4>' . $monthString . '</h4>' . "\n\n"
                            . '<ul class="realblog_archive">' . "\n";
                        foreach ($realbloglist as $key => $field) {
                            $t .= '<li>'
                                //. tag(
                                //    'img src="' . $plugin_images_folder
                                //    . 'realblog_item.gif" alt="realblogitem_img"'
                                //)
                                //. '&nbsp;'
                                . date(
                                    $plugin_tx[$plugin]['date_format'],
                                    $field[REALBLOG_DATE]
                                )
                                . '&nbsp;&nbsp;&nbsp;<a href="' . $sn . '?'
                                . $u[$s] . '&amp;'
                                . str_replace(' ', '_', $field[REALBLOG_TITLE])
                                . '&amp;realblogaction=view&amp;realblogID='
                                . $field[REALBLOG_ID] . '&amp;page=' . $page
                                . '" title="' . $plugin_tx[$plugin]["tooltip_view"]
                                . '">' . $field[REALBLOG_TITLE] . '</a></li>'
                                . "\n";
                        }
                        $t .= '</ul>' . "\n";
                    }
                }
                if (count($generalrealbloglist) == 0) {
                    $t .= $plugin_tx[$plugin]['no_topics'];
                }
            } else {
                $currentMonth = 12;
                $realbloglist = $records;
                $t .= "\n" . '<div>&nbsp;</div>' . "\n";
                if (count($realbloglist) > 0) {
                    foreach ($realbloglist as $key => $field) {
                        $month = date('n', $field[REALBLOG_DATE]);
                        $year = date('Y', $field[REALBLOG_DATE]);
                        $monthString = strftime(
                            '%B %Y', mktime(0, 0, 0, $month, 1, $year)
                        );
                        if ($realblogmonth != $month) {
                            $t .= ($key != 0) ? tag('br') : '';
                            $t .= '<h4>' . $monthString . '</h4>' . "\n";
                            $realblogmonth = $month;
                        }
                        $t .= '<p style="line-height: 1em;">'
                            . date(
                                $plugin_tx[$plugin]['date_format'],
                                $field[REALBLOG_DATE]
                            )
                            . '&nbsp;&nbsp;&nbsp;<a href="' . $sn . '?' . $u[$s]
                            . '&amp;'
                            . str_replace(' ', '_', $field[REALBLOG_TITLE])
                            . '&amp;realblogaction=view&amp;realblogID='
                            . $field[REALBLOG_ID] . '&amp;page=' . $page
                            . '" title="' . $plugin_tx[$plugin]["tooltip_view"]
                            . '">' . $field[REALBLOG_TITLE] . '</a></p>' . "\n\n";
                    }
                } else {
                    $t .= $plugin_tx[$plugin]['no_topics'];
                }
            }
            break;
        }
    } else {
        // Display the realblogitem for the given ID
        $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);
        // Set the return page, based on the caling page
        if ($from_page == '' || empty($from_page)) {
            $from_page = 1;
        }
        $return_page = ($from_page == $u[$s]) ? $u[$s] : $from_page;
        if (count($record) > 0) {
            $articleView = new Realblog_ArticleView($realblogID, $record, $page);
            $t = $articleView->render();
        }
    }
    $c[$s]='';
    unset($realblogaction);
    unset($compClause);
    return $t;
}

/**
 * Displays the realblog topics with a link to the blog page from the template.
 *
 * A page calling #cmsimple $output.=showrealblog();# must exist.
 * Options: realblog_page [required] : this is the page containing the
 *          showrealblog() function
 *
 * @param mixed $options FIXME
 *
 * @return string (X)HTML.
 *
 * @global array  The paths of system files and folders.
 * @global string The script name.
 * @global array  The localization of the plugins.
 * @global array  The configuration of the plugins.
 * @global array  The URLs of the pages.
 * @global int    The current page index.
 * @global array  The contents of the pages.
 * @global array  The headings of the pages.
 * @global string The current language.
 * @global mixed  FIXME
 */
function Realblog_link($options)
{
    global $pth, $sn, $plugin_tx, $plugin_cf, $u, $s, $c, $h, $sl, $page;

    $includeonfrontpage = 'false';
    $realblog_page = '';
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['realblogpage'])) {
        $realblog_page = $arguments['realblogpage'];
    }

    // Check if the specified realblog_page realy exists
    $page_exists = false;
    foreach ($u as $key => $value) {
        if ($realblog_page === $value) {
            if ($s == $key) {
                $page_exists = true;
                break;
            }
            // FIXME: fix for Realblog_blog or remove check alltogether
            if (preg_match('/showrealblog\(.*/is', $c[$key])) {
                $page_exists = true;
                break;
            }
        }
    }

    if (!$page_exists) {
        return '';
    }

    if (!empty($realblog_page) || $realblog_page != '') {
        // Register the current page in a session variable
        $plugin = basename(dirname(__FILE__), '/');

        // set general variables for the plugin
        $plugin_images_folder = $pth['folder']['plugins'] . $plugin . '/images/';

        $db = Realblog_connect();

        if (@$id == -1 || empty($id) || !isset($id)) {
            if ($plugin_cf['realblog']['links_visible'] > 0) {
                $t = '<p class="realbloglink">'
                    . $plugin_tx['realblog']['links_visible_text'] . '</p>';
                // Select all published realblog items ordered by DATE
                // descending within the publishing range
                $compClause = null;
                $compClause = new AndWhereClause(
                    new SimpleWhereClause(
                        REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON
                    )
                );
                if (strtolower($includeonfrontpage) === 'true') {
                    $compClause = new OrWhereClause(
                        new SimpleWhereClause(
                            REALBLOG_FRONTPAGE, '=', 'on', STRING_COMPARISON
                        ),
                        $compClause
                    );
                }
                $realbloglist = $db->selectWhere(
                    'realblog.txt', $compClause, -1,
                    array(
                        new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                        new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                    )
                );
                // Show the results
                $max_visible = $plugin_cf[$plugin]['links_visible'];
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
                                $plugin_tx[$plugin]['display_date_format'],
                                $record[REALBLOG_DATE]
                            )
                            . "\n" . '</div>';
                        $t .= "\n" . '<div class="realblog_tpl_show_title">' . "\n"
                            . '<a href="' . $sn . '?' . $realblog_page
                            . '&amp;realblogaction=view&amp;realblogID='
                            . $record[REALBLOG_ID] . '&amp;page=1">'
                            . $record[REALBLOG_TITLE] .'</a>' . "\n"
                            . '</div>' . "\n";
                        // Limit the number of visible realblog items (set in
                        // the configuration; empty=all realblog)
                        if ($plugin_cf[$plugin]['links_visible'] > 0) {
                            if ($realblog_counter == $max_visible) {
                                break;
                            }
                        }
                    }
                    $t .= "\n" . '<div style="clear: both;"></div></div>' . "\n";
                } else {
                    $t .= $plugin_tx[$plugin]['no_topics'];
                }
                //$t.='</div>' . "\n";
            }
        }
    } else {
        // FIXME
        $t .= '';
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
 * @return FIXME
 *
 * @global array The configuration of the plugins.
 * @global array The localization of the plugins.
 *
 * @todo reuse Realblog_getCalendarDateFormat
 */
function Realblog_makeTimestampDates1($tmpdate = null)
{
    global $plugin_cf, $plugin_tx;

    // get plugin name
    $plugin = basename(dirname(__FILE__), '/');
    $my_date_format = explode('/', $plugin_tx[$plugin]['date_format']);
    if (count($my_date_format) > 1) {
        $date_separator = '/';
    } else {
        $my_date_format = explode('.', $plugin_tx[$plugin]['date_format']);
        if (count($my_date_format) > 1) {
            $date_separator = '.';
        } else {
            $my_date_format = explode('-', $plugin_tx[$plugin]['date_format']);
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
    foreach ($cal_date_format as $key => $value) {
        $cal_format .= $value;
    }
    foreach ($regex as $key => $value) {
        if ($key < (count($regex) - 1)) {
            $regex_format .= $value . $date_separator;
        } else {
            $regex_format .= $value;
        }
    }
    if ($tmpdate == null) {
        $tmpdate = date($plugin_tx[$plugin]['date_format']);
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
 * @return FIXME
 *
 * @global array  The localization of the core.
 * @global array  The paths of system files and folders.
 * @global string The script name.
 * @global array  The URLs of the pages.
 * @global int    The current page index.
 * @global array  The contents of the pages.
 * @global string The current language.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 * @global mixed  FIXME
 */
function Realblog_exportRssFeed()
{
    global $tx, $pth, $sn, $u, $s, $c, $sl, $plugin_cf, $plugin_tx, $page;

    $plugin = basename(dirname(__FILE__), '/');
    // set general variables for the plugin
    // FIXME: DON'T
    include_once $pth['folder']['plugins'] . $plugin . '/config/config.php';
    include_once $pth['folder']['plugins'] . $plugin . '/languages/' . $sl . '.php';

    if (strtolower($plugin_tx[$plugin]['rss_enable']) == 'true') {

        $rss_path = './';
        $plugin_images_folder = $pth['folder']['plugins'] . $plugin . '/images/';

        $db = Realblog_connect();

        if ($fp = @fopen($rss_path . "realblog_rss_feed.xml", "w+")) {
            fputs(
                $fp,
                '<?xml version="1.0" encoding="'
                . strtolower($plugin_cf[$plugin]['rss_encoding'])
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
                '<title>' . $plugin_tx[$plugin]['rss_title'] . '</title>' . "\n"
            );
            fputs(
                $fp,
                '<link>' . $plugin_tx['realblog']['rss_page'] . '</link>' . "\n"
            );
            fputs(
                $fp,
                '<description>' . $plugin_tx[$plugin]['rss_description']
                . '</description>' . "\n"
            );
            fputs(
                $fp,
                '<language>' . $plugin_tx[$plugin]['rss_language'] . '</language>'
                . "\n"
            );
            fputs(
                $fp,
                '<copyright>' . $plugin_cf[$plugin]['rss_copyright']
                . '</copyright>' . "\n"
            );
            fputs(
                $fp,
                '<managingEditor>' . $plugin_cf[$plugin]['rss_editor']
                . '</managingEditor>' . "\n"
            );
            fputs(
                $fp,
                '<image>' . "\n"
            );
            fputs(
                $fp,
                '<title>' . $plugin_tx[$plugin]['rss_title'] . '</title>' . "\n"
            );
            fputs(
                $fp,
                '<url>' . $plugin_cf[$plugin]['rss_logo'] . '</url>' . "\n"
            );
            fputs(
                $fp,
                '<link>' . $plugin_tx['realblog']['rss_page'] . '</link>' . "\n"
            );
            fputs($fp, '<width>65</width>'. "\n");
            fputs($fp, '<height>35</height>' . "\n");
            fputs(
                $fp,
                '<description>' . $plugin_tx[$plugin]['rss_description']
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
                        // FIXME
                        . htmlspecialchars(stripslashes($record[REALBLOG_TITLE]))
                        . '</title>' . "\n";
                    $link = '<link>' . $plugin_tx[$plugin]["rss_page"]
                        . '&amp;realblogaction=view&amp;realblogID='
                        . $record[REALBLOG_ID] . '&amp;page=' . $page . '</link>'
                        . "\n";
                    $description = '<description>'
                        // FIXME
                        . preg_replace(
                            '/({{{PLUGIN:.*?}}}|{{{function:.*?}}}|#CMSimple .*?#)/'
                            . 'is',
                            '',
                            // FIXME
                            htmlspecialchars(
                                stripslashes($record[REALBLOG_HEADLINE])
                            )
                        )
                        . '</description>' . "\n";
                    // FIXME date('r')?
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
        include_once $pth['folder']['plugins'] . 'realblog/classes/fields.php';
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

    $my_date_format1 = explode('/', $plugin_tx['realblog']['date_format']);
    if (count($my_date_format1) > 1) {
        $date_separator1 = '/';
    } else {
        $my_date_format1 = explode('.', $plugin_tx['realblog']['date_format']);
        if (count($my_date_format1) > 1) {
            $date_separator1 = '.';
        } else {
            $my_date_format1 = explode('-', $plugin_tx['realblog']['date_format']);
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

?>
