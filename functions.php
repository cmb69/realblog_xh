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
 * Renders the search form.
 *
 * @param string $realblogYear The selected year.
 *
 * @return string (X)HTML.
 *
 * @global string The script name.
 * @global string The URL of the current page.
 * @global array  The paths of system files and folders.
 * @global array  The localization of the core.
 * @global array  The localization of the plugins.
 *
 * @todo make GET form
 */
function Realblog_renderSearchForm($realblogYear)
{
    global $sn, $su, $pth, $tx, $plugin_tx;

    $ptx = $plugin_tx['realblog'];
    $t = "\n" . '<div>' . "\n" .
        '<form name="realblogsearch" method="post" action="' . $sn . "?"
        . $su . '">';
    $t .= "\n" . '<div id="enablesearch">' . "\n"
        // FIXME: javascript protocol
        . '<a href="javascript:realblog_showSearch()">' . "\n"
        . tag(
            'img id="btn_img" alt="searchbuttonimg" src="'
            . $pth['folder']['plugins'] . 'realblog/images/btn_expand.gif" title="'
            . $ptx['tooltip_showsearch']
            . '" style="border: 0;"'
        )
        . '</a>' . "\n" . '&nbsp;<b>' . $tx['search']['button']
        . '</b>' . "\n" . '</div>' . "\n";
    $t .= "\n" . '<div id="searchblock" style="display:none">' . "\n";
    $t .= tag('input type="hidden" name="realblogaction" value="search"');
    $t .= tag(
        'input type="hidden" name="realblogYear" value="'
        . $realblogYear . '"'
    );
    $t .= '<p class="realblog_search_hint">'
        . $ptx['search_hint'] . '</p>';
    $t .= "\n" . '<table style="width: 100%;">' . "\n";
    $t .= '<tr>' . "\n"
        . '<td style="width: 30%;" class="realblog_search_text">'
        . $ptx['title_label'] . ' '
        . $ptx['search_contains'] . ':' . "\n"
        . '</td>' . "\n" . '<td>' . "\n"
        . '<select name="title_operator"'
        . ' style="visibility: hidden; width: 0;">' . "\n"
        . '<option value="2" selected="selected">'
        . $ptx['search_contains'] . '</option>' . "\n"
        . '</select>' . "\n"
        . tag(
            'input type="text" name="realblog_title" size="35"'
            . ' class="realblog_search_input" maxlength="64"'
        )
        . "\n" . '</td>' . "\n" . '</tr>' . "\n";
    $t .= '<tr>' . "\n" . '<td style="width: 30%;">&nbsp;</td>' . "\n"
        . '<td>' . "\n" . '&nbsp;&nbsp;&nbsp;'
        . tag(
            'input id="operator_2a" type="radio" name="operator_2"'
            . ' value="AND"'
        )
        . '&nbsp;' . $ptx['search_and'] . '&nbsp;&nbsp;&nbsp;'
        . tag(
            'input id="operator_2b" type="radio" name="operator_2"'
            . ' value="OR" checked="checked"'
        )
        . '&nbsp;' .  $ptx['search_or'] . '</td>' . "\n"
        . '</tr>' . "\n";
    $t .= '<tr>' . "\n" .
        '<td style="width: 30%;" class="realblog_search_text">'
        . $ptx['story_label'] . ' '
        . $ptx['search_contains'] . ':' . '</td>'
        . '<td><select name="story_operator"'
        . ' style="visibility: hidden; width: 0;">'
        . '<option value="2" selected="selected">'
        . $ptx['search_contains']
        . '</option>' . "\n" . '</select>' . "\n"
        . tag(
            'input type="text" name="realblog_story" size="35"'
            . ' class="realblog_search_input" maxlength="64"'
        )
        . '</td></tr>' . "\n";
    $t .= '<tr>' . "\n" . '<td colspan="2">&nbsp;</td></tr>' . "\n";
    $t .= '<tr>' . "\n" . '<td colspan="2" style="text-align: center;">'
        . tag(
            'input type="submit" name="send" value="'
            . $tx['search']['button'] . '"'
        )
        . '</td></tr>' . "\n";
    $t .= '</table>' . "\n" . '</div>' . "\n";
    $t .= '</form>' . "\n";
    $t .= '</div>' . "\n";
    return $t;
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
            $_REQUEST['title_operator']
        );
    }
    if (!empty($_REQUEST['realblog_story'])) {
        $compClauseStory = new LikeWhereClause(
            REALBLOG_STORY, $_REQUEST['realblog_story'],
            $_REQUEST['story_operator']
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
        case 'OR':
            $compClause = new OrWhereClause(
                $compClauseTitle, $compClauseStory
            );
            break;
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
        case 'OR':
            $compClause = new OrWhereClause(
                $compClauseDate2, $compClauseStory
            );
            break;
        }
        break;
    case 6:
        switch ($_REQUEST['operator_1']) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClauseDate2, $compClauseTitle
            );
            break;
        case 'OR':
            $compClause = new OrWhereClause(
                $compClauseDate2, $compClauseTitle
            );
            break;
        }
        break;
    case 7:
        $compClause = $compClauseDate2;
        switch ($_REQUEST['operator_1']) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseTitle);
            break;
        case 'OR':
            $compClause = new OrWhereClause($compClause, $compClauseTitle);
            break;
        }
        switch ($_REQUEST['operator_2']) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseStory);
            break;
        case 'OR':
            $compClause = new OrWhereClause($compClause, $compClauseStory);
            break;
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
        case 'OR':
            $compClause = new OrWhereClause(
                $compClauseDate1, $compClauseStory
            );
            break;
        }
        break;
    case 10:
        switch ($_REQUEST['operator_1']) {
        case 'AND':
            $compClause = new AndWhereClause(
                $compClauseDate1, $compClauseTitle
            );
            break;
        case 'OR':
            $compClause = new OrWhereClause(
                $compClauseDate1, $compClauseTitle
            );
            break;
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
        case 'OR':
            $compClause = new OrWhereClause(
                $compClause, $compClauseTitle
            );
            break;
        }
        switch ($_REQUEST['operator_2']) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseStory);
            break;
        case 'OR':
            $compClause = new OrWhereClause($compClause, $compClauseStory);
            break;
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
        case 'OR':
            $compClause = new OrWhereClause(
                new AndWhereClause($compClauseDate1, $compClauseDate2),
                $compClauseStory
            );
            break;
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
        case 'OR':
            $compClause = new OrWhereClause(
                new AndWhereClause($compClauseDate1, $compClauseDate2),
                $compClauseTitle
            );
            break;
        }
        break;
    case 15:
        $compClause = new AndWhereClause($compClauseDate1, $compClauseDate2);
        switch ($_REQUEST['operator_1']) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseTitle);
            break;
        case 'OR':
            $compClause = new OrWhereClause($compClause, $compClauseTitle);
            break;
        }
        switch ($_REQUEST['operator_2']) {
        case 'AND':
            $compClause = new AndWhereClause($compClause, $compClauseStory);
            break;
        case 'OR':
            $compClause = new OrWhereClause($compClause, $compClauseStory);
            break;
        }
        break;
    }
    return $compClause;
}

?>
