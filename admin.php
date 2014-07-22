<?php

/**
 * The back-end functionality.
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

/*
 * Prevent direct access and usage from unsupported CMSimple_XH versions.
 */
if (!defined('CMSIMPLE_XH_VERSION')
    || strpos(CMSIMPLE_XH_VERSION, 'CMSimple_XH') !== 0
    || version_compare(CMSIMPLE_XH_VERSION, 'CMSimple_XH 1.6', 'lt')
) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=UTF-8');
    die(<<<EOT
Realblog_XH detected an unsupported CMSimple_XH version.
Uninstall Realblog_XH or upgrade to a supported CMSimple_XH version!
EOT
    );
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

if (isset($realblog) && $realblog == 'true') {
    initvar('admin');
    initvar('action');

    $temp = new Realblog_AdminController();
    $temp->dispatch();
}

/**
 * FIXME: ???
 *
 * @param string $realblogID FIXME
 * @param string $action     FIXME
 * @param int    $ret_page   FIXME
 *
 * @return string (X)HTML.
 *
 * @global array  The paths of system files and folders.
 * @global string The name of the current plugin.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.#
 * @global string The script name.
 * @global string FIXME
 * @global string FIXME
 * @global array  The configuration of the core.
 * @global array  The localization of the core.
 * @global mixed  FIXME
 */
function Realblog_form($realblogID = null, $action = null, $ret_page = 1)
{
    global $pth, $plugin, $plugin_cf, $plugin_tx, $sn, $plugin_images_folder,
        $cf, $tx, $cal_format;

    $db = Realblog_connect();

    switch ($action){
    case 'add_realblog':
        $title = $plugin_tx[$plugin]['tooltip_add'];
        $realblog_date = date($plugin_tx[$plugin]['date_format']);
        $realblog_startdate = date($plugin_tx[$plugin]['date_format']);
        $realblog_enddate = date($plugin_tx[$plugin]['date_format']);
        break;
    case 'modify_realblog':
        $title = $plugin_tx[$plugin]['tooltip_modify'] . ' [ID: '
            . $realblogID . ']';
        break;
    case 'delete_realblog':
        $title = $plugin_tx[$plugin]['tooltip_delete'] . ' [ID: '
            . $realblogID . ']';
        break;
    }

    if ($action === 'modify_realblog' || $action === 'delete_realblog') {
        $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $realblogID);
        $realblog_id = $record[REALBLOG_ID];
        $realblog_date = date(
            $plugin_tx[$plugin]['date_format'], $record[REALBLOG_DATE]
        );
        $realblog_title = $record[REALBLOG_TITLE];
        $realblog_headline = $record[REALBLOG_HEADLINE];
        $realblog_story = $record[REALBLOG_STORY];
        $realblog_frontpage = $record[REALBLOG_FRONTPAGE];
        $realblog_startdate = date(
            $plugin_tx[$plugin]['date_format'], $record[REALBLOG_STARTDATE]
        );
        $realblog_enddate = date(
            $plugin_tx[$plugin]['date_format'], $record[REALBLOG_ENDDATE]
        );
        $realblog_status = $record[REALBLOG_STATUS];
        $realblog_rssfeed = $record[REALBLOG_RSSFEED];
        $realblog_comments = $record[REALBLOG_COMMENTS];
        // FIXME: what's $status?
        unset($status);
        $status[$realblog_status] = 'selected';
        $checked = ($realblog_frontpage == 'on') ? 'CHECKED' : '';
        $rsschecked = ($realblog_rssfeed == 'on') ? 'CHECKED' : '';
        $commentschecked = ($realblog_comments == 'on') ? 'CHECKED' : '';
    }

    // Display realblog item form
    $temp = new Realblog_ArticleAdminView(
        @$realblog_id, $realblog_date, $realblog_startdate, $realblog_enddate,
        @$status, @$commentschecked, @$rsschecked, @$realblog_title,
        @$realblog_headline, @$realblog_story, $action, $ret_page
    );
    return $temp->render();
}

/**
 * FIXME
 *
 * @param mixed $tmpdate FIXME
 *
 * @return FIXME
 *
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 * @global string The name of the current plugin.
 *
 * @todo Realblog_makeTimestampDates1() in index.php
 */
function Realblog_makeTimestampDates($tmpdate = null)
{
    global $plugin_cf, $plugin_tx, $plugin;

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

    foreach ($cal_date_format as $key => $value) {
        @$cal_format .= $value;
    }

    foreach ($regex as $key => $value) {
        if ($key < (count($regex) - 1)) {
            @$regex_format .= $value . $date_separator;
        } else {
            $regex_format .= $value;
        }
    }

    if ($tmpdate == null) {
        $tmpdate = date($plugin_tx[$plugin]['date_format']);
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
 * @param string $title FIXME
 * @param string $info  FIXME
 * @param int    $page  FIXME
 *
 * @return string (X)HTML.
 *
 * @global array             The localization of the plugins.
 * @global string            The name of the current plugin.
 * @global string            The script name.
 */
function Realblog_dbconfirm($title, $info, $page)
{
    global $plugin_tx, $plugin, $sn;

    if (!isset($page)) {
        $page = $_SESSION['page'];
    }

    $t = '<h1>Realblog &ndash; ' . $title . '</h1>';
    $t .= '<div>&nbsp;</div>';
    $t .= '<form name="confirm" method="post" action="' . $sn . '?&amp;'
        . $plugin . '&amp;admin=plugin_main">';
    $t .= '<table width="100%"><tbody>';
    $t .= '<tr><td class="realblog_confirm_info" align="center">'
        . $info . '</td></tr><tr><td>&nbsp;</td></tr>';
    $t .= '<tr><td class="realblog_confirm_button" align="center">'
        . tag(
            'input type="button" name="cancel" value="'
            . $plugin_tx[$plugin]['btn_ok'] . '" onclick=\'location.href="'
            . $sn . '?&amp;' . $plugin . '&amp;admin=plugin_main'
            . '&amp;action=plugin_text&amp;page=' . $page . '"\''
        )
        . '</td></tr>';
    $t .= '</tbody></table></form>';
    $t .= '<div>&nbsp;</div>';
    return $t;
}

?>
