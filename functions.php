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
 * Returns the search clause.
 *
 * @return CompositeWhereClause
 *
 * @todo realblog_from_date and realblog_to_date are unused!
 */
function Realblog_searchClause()
{
    if (!empty($_REQUEST['realblog_from_date'])) {
        $compClauseDate1 = new SimpleWhereClause(
            REALBLOG_DATE, $_REQUEST['date_operator_1'],
            Realblog_makeTimestampDates1($_REQUEST['realblog_from_date'])
        );
    }
    if (!empty($_REQUEST['realblog_to_date'])) {
        $compClauseDate2 = new SimpleWhereClause(
            REALBLOG_DATE, $_REQUEST['date_operator_2'],
            Realblog_makeTimestampDates1($_REQUEST['realblog_to_date'])
        );
    }
    if (!empty($_REQUEST['realblog_title'])) {
        $compClauseTitle = new LikeWhereClause(
            REALBLOG_TITLE, $_REQUEST['realblog_title'],
            2 // TODO: $_REQUEST['title_operator']
        );
    }
    if (!empty($_REQUEST['realblog_story'])) {
        $compClauseStory = new LikeWhereClause(
            REALBLOG_STORY, $_REQUEST['realblog_story'],
            2 // TODO: $_REQUEST['story_operator']
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
        switch ($_REQUEST['operator_2']) {
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
        switch ($_REQUEST['operator_2']) {
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
        switch ($_REQUEST['operator_1']) {
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
        switch ($_REQUEST['operator_1']) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseTitle);
            break;
        default:
            $compClause = new OrWhereClause($compClause, $compClauseTitle);
        }
        switch ($_REQUEST['operator_2']) {
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
        switch ($_REQUEST['operator_2']) {
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
        switch ($_REQUEST['operator_1']) {
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
        switch ($_REQUEST['operator_1']) {
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
        switch ($_REQUEST['operator_2']) {
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
        switch ($_REQUEST['operator_2']) {
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
        switch ($_REQUEST['operator_1']) {
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
        switch ($_REQUEST['operator_1']) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseTitle);
            break;
        default:
            $compClause = new OrWhereClause($compClause, $compClauseTitle);
        }
        switch ($_REQUEST['operator_2']) {
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
 * @global string The script name.
 * @global string The URL of the current page.
 * @global array  The localization of the plugins.
 */
function Realblog_renderSearchResults($what, $count)
{
    global $sn, $su, $plugin_tx;

    $key = ($what == 'archive') ? 'back_to_archive' : 'search_show_all';
    $title = Realblog_getPgParameter('realblog_title');
    $story = Realblog_getPgParameter('realblog_story');
    $operator = Realblog_getPgParameter('operator_2');
    $operator = ($operator == 'AND')
        ? $plugin_tx['realblog']['search_and']
        : $plugin_tx['realblog']['search_or'];
    $words = '"' . $title . '" ' . $operator . ' "' . $story . '"';
    return '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>'
        . XH_hsc($words) . '</b></p>'
        . '<p>' . $plugin_tx['realblog']['search_result'] . '<b> '
        . $count . '</b></p>'
        . '<p><a href="' . $sn . '?' . $su . '"><b>'
        . $plugin_tx['realblog'][$key] . '</b></a></p>';
}

?>
