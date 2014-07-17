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

?>
