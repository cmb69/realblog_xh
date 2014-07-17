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

if (!function_exists('sv')
    || preg_match('#/plugins/realblog/admin.php#i', $_SERVER['SCRIPT_NAME'])
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

/**
 * Writes the required references to the head element.
 *
 * @return void
 *
 * @global array The paths of system files and folders.
 * @global string The current language.
 * @global string The (X)HTML fragment to insert in the head element.
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

    Realblog_useCalendar();
    if ($action == 'delete_realblog' || $action == 'add_realblog'
        || $action == 'modify_realblog'
    ) {
        init_editor(array('realblog_headline_field', 'realblog_story_field'));
    }

    // set general variables for the plugin
    // TO DO: check if these folders exist - if not, exit plugin...
    $plugin_images_folder = $pth['folder']['plugins'] . $plugin . '/images/';
    $db_name = 'realblog.txt';
    $o .= print_plugin_admin('on');
    if ($admin <> 'plugin_main') {
        $o .= plugin_admin_common($action, $admin, $plugin);
    }
    if ($admin == '') {
        $o.= tag('br') . '<div id="realblog_version"><h4>Realblog_XH '
            . REALBLOG_VERSION
            . '</h4><p>by <a href="http://www.ge-webdesign.de/cmsimplerealblog"'
            . ' target="_blank">ge-webdesign.de</a>'
            . ' (released: ' . $realblog_release . ')</p><p>'
            . $realblog_based_on . '</p></div>';
    }

    $db = Realblog_connect();

    if ($admin == 'plugin_main'
        && is_writable($pth['folder']['content'] . 'realblog/realblog.txt')
    ) {
        $cal_format = Realblog_getCalendarDateFormat();

        $realblogID = Realblog_getPgParameter('realblogID');
        $page = Realblog_getPgParameter('page');
        $do = Realblog_getPgParameter('do');
        $filter = Realblog_getPgParameter('filter');
        $realblog_id = Realblog_getPgParameter('realblog_id');
        $realblog_date = Realblog_getPgParameter('realblog_date');
        $realblog_title = Realblog_getPgParameter('realblog_title');
        $realblog_headline = Realblog_getPgParameter('realblog_headline');
        $realblog_story = Realblog_getPgParameter('realblog_story');
        $realblog_frontpage = Realblog_getPgParameter('realblog_frontpage');
        $realblog_rssfeed = Realblog_getPgParameter('realblog_rssfeed');
        $realblog_comments = Realblog_getPgParameter('realblog_comments');
        $realblog_startdate = Realblog_getPgParameter('realblog_startdate');
        $realblog_enddate = Realblog_getPgParameter('realblog_enddate');
        $realblog_status = Realblog_getPgParameter('realblog_status');
        $filter1 = Realblog_getPgParameter('filter1');
        $filter2 = Realblog_getPgParameter('filter2');
        $filter3 = Realblog_getPgParameter('filter3');
        $realblogtopics = Realblog_getPgParameter('realblogtopics');
        $batchdelete_x = Realblog_getPgParameter('batchdelete_x');
        $changestatus_x = Realblog_getPgParameter('changestatus_x');
        $batchchangestatus = Realblog_getPgParameter('batchchangestatus');
        $new_realblogstatus = Realblog_getPgParameter('new_realblogstatus');

        // perform the appropriate action for the selected record
        if ($action == 'delete_realblog' || $action == 'add_realblog'
            || $action == 'modify_realblog'
        ) {
            $o .= Realblog_form($realblogID, $action, $page);
        }

        // add new realblog item to the database or modify the selected realblog item
        if ($do == 'add' || $do == 'modify') {
            $realblog_date = Realblog_makeTimestampDates($realblog_date);
            $realblogitem[REALBLOG_DATE] = $realblog_date;
            // FIXME: don't stripslashes()
            $realblogitem[REALBLOG_TITLE] = stripslashes($realblog_title);
            $realblogitem[REALBLOG_HEADLINE] = stripslashes($realblog_headline);
            $realblogitem[REALBLOG_STORY] = stripslashes($realblog_story);
            $realblogitem[REALBLOG_FRONTPAGE] = $realblog_frontpage;
            $realblogitem[REALBLOG_STARTDATE] = Realblog_makeTimestampDates(
                $realblog_startdate
            );
            $realblogitem[REALBLOG_ENDDATE] = Realblog_makeTimestampDates(
                $realblog_enddate
            );
            $realblogitem[REALBLOG_STATUS] = $realblog_status;
            $realblogitem[REALBLOG_RSSFEED] = $realblog_rssfeed;
            $realblogitem[REALBLOG_COMMENTS] = $realblog_comments;

            if ($do == 'add') {
                $realblogitem[REALBLOG_ID] = '0'; // dummy
                $newId = $db->insertWithAutoId($db_name, REALBLOG_ID, $realblogitem);
                $title = $plugin_tx[$plugin]['tooltip_add'];
                $info = '<h5>' . $plugin_tx[$plugin]['story_added'] . '</h5>';
                $o .= Realblog_dbconfirm($title, $info, $page);
            }

            if ($do == 'modify') {
                $realblogitem[REALBLOG_ID] = $realblog_id;
                $newId = $db->updateRowById($db_name, REALBLOG_ID, $realblogitem);
                $title = $plugin_tx[$plugin]['tooltip_modify'];
                $info = '<h5>' . $plugin_tx[$plugin]['story_modified'] . '</h5>';
                $o .= Realblog_dbconfirm($title, $info, $page);
            }
        }

        // delete the selected realblog item
        if ($do == 'delete') {
            $page = $_SESSION['page'];
            $db->deleteWhere(
                $db_name,
                new SimpleWhereClause(REALBLOG_ID, '=', $realblog_id),
                INTEGER_COMPARISON
            );
            $title = $plugin_tx[$plugin]['tooltip_delete'];
            $info = '<h5>' . $plugin_tx[$plugin]['story_deleted'] . '</h5>';
            $o .= Realblog_dbconfirm($title, $info, $page);
        }

        // batch delete of the selected realblog item
        if ($do == 'delselected') {
            foreach ($realblogtopics as $key => $delrealblog_id) {
                $db->deleteWhere(
                    $db_name,
                    new SimpleWhereClause(REALBLOG_ID, '=', $delrealblog_id),
                    INTEGER_COMPARISON
                );
            }
            $title = $plugin_tx[$plugin]['tooltip_deleteall'];
            $info = $plugin_tx[$plugin]['deleteall_done'];
            $o .= Realblog_dbconfirm($title, $info, $page);
        }

        // batch status change of the selected realblog item
        if ($do == 'batchchangestatus') {
            if (isset($_SESSION['page'])) {
                $page = $_SESSION['page'];
            }

            if ($new_realblogstatus != ''
                && ($new_realblogstatus == 0 || $new_realblogstatus == 1
                || $new_realblogstatus == 2)
            ) {
                foreach ($realblogtopics as $key => $changerealblog_id) {
                    $realblogitem[REALBLOG_ID] = $changerealblog_id;
                    $realblogitem[REALBLOG_STATUS] = $new_realblogstatus;
                    $db->updateRowById($db_name, REALBLOG_ID, $realblogitem);
                }
                $title = $plugin_tx[$plugin]['tooltip_changestatus'];
                $info = $plugin_tx[$plugin]['changestatus_done'];
                $o .= Realblog_dbconfirm($title, $info, $page);
            } else {
                $title = $plugin_tx[$plugin]['tooltip_changestatus'];
                $info = $plugin_tx[$plugin]['nochangestatus_done'];
                $o .= Realblog_dbconfirm($title, $info, $page);
            }
        }

        //
        if ($action === 'plugin_text' || $action=== 'edit') {
            // delete the selected realblog items
            if (isset($batchdelete_x)) {
                $title = $plugin_tx[$plugin]['tooltip_deleteall'];
                $info = $plugin_tx[$plugin]['confirm_deleteall'];

                if (count($realblogtopics) > 0) {
                    // Confirm batch delete of the selected realblog topics
                    // FIXME: append to $o ?
                    $o = '<div><h4>Realblog_XH ' . REALBLOG_VERSION . ' : ' . $title
                        . '</h4></div>';
                    $o .= '<div>&nbsp;</div>';
                    $o .= '<form name="confirm" method="post" action="' . $sn
                        . '?&amp;' . $plugin . '&amp;admin=plugin_main">';
                    $o .= '<table width="100%"><tbody>';

                    foreach ($realblogtopics as $value => $key) {
                        $o.= tag(
                            'input type="hidden" name="realblogtopics[]" value="'
                            . $key . '"'
                        );
                    }

                    $o .= tag(
                        'input type="hidden" name="page" value="' . $page . '"'
                    );
                    $o .= tag('input type="hidden" name="do" value="delselected"');
                    $o .= '<tr><td class="reablog_confirm_info" align="center">'
                        . $info . '</td></tr><tr><td>&nbsp;</td></tr>';
                    $o .= '<tr><td class="reablog_confirm_button" align="center">'
                        . tag(
                            'input type="submit" name="submit" value="'
                            . $plugin_tx[$plugin]['btn_delete'] . '"'
                        )
                        . '&nbsp;&nbsp;'
                        . tag(
                            'input type="button" name="cancel" value="&nbsp;'
                            . $plugin_tx[$plugin]['btn_cancel'] . '" onclick=\''
                            . 'location.href="' . $sn . '?&amp;' . $plugin
                            . '&amp;admin=plugin_main&amp;action=plugin_text'
                            . '&amp;page=' . $page . '"\''
                        )
                        . '</td></tr>';
                    $o .= '</tbody></table></form>';
                    $o .= '<div>&nbsp;</div>';
                } else {
                    // Nothing selected
                    $info = $plugin_tx[$plugin]['nothing_selected'];
                    $o = '<div><h4>Realblog_XH ' . REALBLOG_VERSION . ' : ' . $title
                        . '</h4></div>';
                    $o .= '<div>&nbsp;</div>';
                    $o .= '<form name="confirm" method="post" action="' . $sn
                        . '?&amp;' . $plugin
                        . '&amp;admin=plugin_main&amp;action=plugin_text">';
                    $o .= '<table width="100%"><tbody>';
                    $o .= '<tr><td class="reablog_confirm_info" align="center">'
                        . $info . '</td></tr><tr><td>&nbsp;</td></tr>';
                    $o .= '<tr><td class="reablog_confirm_button" align="center">'
                        . tag(
                            'input type="button" name="cancel" value="'
                            . $plugin_tx[$plugin]['btn_ok'] . '" onclick=\''
                            . 'location.href="' . $sn . '?&amp;' . $plugin
                            . '&amp;admin=plugin_main&amp;action=plugin_text'
                            . '&amp;page=' . $page . '"\''
                        )
                        . '</td></tr>';
                    $o .= '</tbody></table></form>';
                    $o .= '<div>&nbsp;</div>';
                }
                return $o; // FIXME: why return $o?
            }

            // batch status change of the selected realblog items
            if (isset($changestatus_x)) {
                // session_register ('page'); // removed in php 5.4
                $_SESSION['page'] = $page;
                $title = $plugin_tx[$plugin]['tooltip_changestatus'];
                $info = $plugin_tx[$plugin]['confirm_changestatus'];

                if (count($realblogtopics) > 0) {
                    // Confirm batch status change of the selected realblog topics
                    $o = '<div><h4>Realblog_XH ' . REALBLOG_VERSION . ' : '
                        . $title . '</h4></div>';
                    $o .= '<div>&nbsp;</div>';
                    $o .= '<form name="confirm" method="post" action="' . $sn
                        . '?&amp;' . $plugin . '&amp;admin=plugin_main">';
                    $o .= '<table width="100%"><tbody>';

                    foreach ($realblogtopics as $value => $key) {
                        $o.= tag(
                            'input type="hidden" name="realblogtopics[]" value="'
                            . $key . '"'
                        );
                    }

                    $o .= tag(
                        'input type="hidden" name="page" value="' . $page . '"'
                    );
                    $o .= tag(
                        'input type="hidden" name="do" value="batchchangestatus"'
                    );
                    $o .= '<td width="100%" align="center">'
                        . '<select name="new_realblogstatus">'
                        . '<option value="">'
                        . $plugin_tx[$plugin]['entry_status'] . '</option>'
                        . '<option value="0">'
                        . $plugin_tx[$plugin]['readyforpublishing'] . '</option>'
                        . '<option value="1">'
                        . $plugin_tx[$plugin]['published'] . '</option>'
                        . '<option value="2">'
                        . $plugin_tx[$plugin]['archived'] . '</option>'
                        . '</select></td>';
                    $o .= '<tr><td class="realblog_confirm_info" align="center">'
                        . $info . '</td></tr><tr><td>&nbsp;</td></tr>';
                    $o .= '<tr><td class="realblog_confirm_button" align="center">'
                        . tag(
                            'input type="submit" name="submit" value="'
                            . $plugin_tx[$plugin]['btn_ok'] . '"'
                        )
                        . '&nbsp;&nbsp;'
                        . tag(
                            'input type="button" name="cancel" value="'
                            . $plugin_tx[$plugin]['btn_cancel'] . '" onclick=\''
                            . 'location.href="' . $sn . '?&amp;' . $plugin
                            . '&amp;admin=plugin_main&amp;action=plugin_text'
                            . '&amp;page=' . $page . '"\''
                        )
                        . '</td></tr>';
                    $o .= '</tbody></table></form>';
                    $o .= '<div>&nbsp;</div>';
                } else {
                    // Nothing selected
                    $info = $plugin_tx[$plugin]['nothing_selected'];
                    $o = '<div><h4>Realblog_XH ' . REALBLOG_VERSION . ' : ' . $title
                        . '</h4></div>';
                    $o .= '<div>&nbsp;</div>';
                    $o .= '<form name="confirm" method="post" action="' . $sn
                        . '?&amp;' . $plugin . '&amp;admin=plugin_main">';
                    $o .= '<table width="100%"><tbody>';
                    $o .= '<tr><td class="realblog_confirm_info" align="center">'
                        . $info . '</td></tr><tr><td>&nbsp;</td></tr>';
                    $o .= '<tr><td class="realblog_confirm_button" align="center">'
                        . tag(
                            'input type="button" name="cancel" value="'
                            . $plugin_tx[$plugin]['btn_ok'] . '" onclick=\''
                            . 'location.href="' . $sn . '?&amp;' . $plugin
                            . '&amp;admin=plugin_main&amp;action=plugin_text'
                            . '&amp;page=' . $page . '"\''
                        )
                        . '</td></tr>';
                    $o .= '</tbody></table></form>';
                    $o .= '<div>&nbsp;</div>';
                }
                return $o; // FIXME: why return $o?
            }

            if ($filter != 'true') {
                $compClause = null;
            } else {
                if ($filter1 == 'on' && $filter2 == 'on' && $filter3 == 'on'
                    || $filter1 != 'on' && $filter2 != 'on' && $filter3 != 'on'
                ) {
                    $compClause = null;
                }
                if ($filter1 == 'on' && $filter2 != 'on' && $filter3 != 'on') {
                    $compClause = new SimpleWhereClause(REALBLOG_STATUS, "=", 0);
                }
                if ($filter1 != 'on' && $filter2 == 'on' && $filter3 != 'on') {
                    $compClause = new SimpleWhereClause(REALBLOG_STATUS, "=", 1);
                }

                if ($filter1 != 'on' && $filter2 != 'on' && $filter3 == 'on') {
                    $compClause = new SimpleWhereClause(REALBLOG_STATUS, "=", 2);
                }
                if ($filter1 == 'on' && $filter2 == 'on' && $filter3 != 'on') {
                    $compClause =new OrWhereClause(
                        new SimpleWhereClause(
                            REALBLOG_STATUS, "=", 0, INTEGER_COMPARISON
                        ),
                        new SimpleWhereClause(
                            REALBLOG_STATUS, "=", 1, INTEGER_COMPARISON
                        )
                    );
                }
                if ($filter1 == 'on' && $filter2 != 'on' && $filter3 == 'on') {
                    $compClause =new OrWhereClause(
                        new SimpleWhereClause(
                            REALBLOG_STATUS, "=", 0, INTEGER_COMPARISON
                        ),
                        new SimpleWhereClause(
                            REALBLOG_STATUS, "=", 2, INTEGER_COMPARISON
                        )
                    );
                }
                if ($filter1 != 'on' && $filter2 == 'on' && $filter3 == 'on') {
                    $compClause =new OrWhereClause(
                        new SimpleWhereClause(
                            REALBLOG_STATUS, "=", 1, INTEGER_COMPARISON
                        ),
                        new SimpleWhereClause(
                            REALBLOG_STATUS, "=", 2, INTEGER_COMPARISON
                        )
                    );
                }
            }

            $records =$db->selectWhere(
                $db_name, $compClause, -1,
                new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
            );
            // Set limit of record in the table
            $page_record_limit = $plugin_cf[$plugin]['admin_records_page'];

            if ($page_record_limit <= 0) {
                $page_record_limit=10;
            }
            if ($page_record_limit >= 50) {
                $page_record_limit=32;
            }

            // Count the total records
            $db_total_records = count($records);
            // FIXME: int cast needs other precedence?
            // Calculate the number of possible pages
            $page_total = ($db_total_records % $page_record_limit == 0)
                ? ((int) $db_total_records / $page_record_limit)
                : ((int) ($db_total_records / $page_record_limit) + 1);
            // Calculate table paging
            $o .= '<div><h4>Realblog_XH ' . REALBLOG_VERSION . ' - '
                . $plugin_tx[$plugin]['story_overview'] . '</h4></div>';

            if ($page > $page_total) {
                $page=1;
            }
            if ($page == '' || $page <= 0 || $page == 1) {
                $start_index = 0;
                $page = 1;
            } else {
                $start_index = ($page - 1) * ($page_record_limit);
            }

            // Display realblog items overview
            // new table layout - GE 2010 - 11

            $tstfilter1 = ($filter1 == 'on') ? 'checked="checked"' : '';
            $tstfilter2 = ($filter2 == 'on') ? 'checked="checked"' : '';
            $tstfilter3 = ($filter3 == 'on') ? 'checked="checked"' : '';
            $o .= "\n" . '<div>' . "\n";
            $o .= "\n" . '<form name="selectstatus" method="post" action="' . $sn
                . '?&amp;' . $plugin
                . '&amp;admin=plugin_main&amp;action=plugin_text">';
            $o .= "\n" . '<table width="100%">' . "\n" . '<tr>';
            $o .= "\n" . '<td width="35%">'
                . tag('input type="checkbox" name="filter1"' . $tstfilter1 . '"')
                . '&nbsp;' . $plugin_tx[$plugin]['readyforpublishing'] . '</td>';
            $o .= "\n" . '<td width="30%">'
                . tag('input type="checkbox" name="filter2"' . $tstfilter2 . '"')
                . '&nbsp;' . $plugin_tx[$plugin]['published'] . '</td>';
            $o .= "\n" . '<td width="30%">'
                . tag('input type="checkbox" name="filter3"' . $tstfilter3 . '"')
                . '&nbsp;' . $plugin_tx[$plugin]['archived'] . '</td>';
            $o .= "\n" . '<td width="5%">'
                . tag(
                    'input type="image" align="middle" src="'
                    . $plugin_images_folder . 'btn_filter.png" name="send"'
                    . ' value="Apply filter" title="'
                    . $plugin_tx[$plugin]['btn_search']
                    . '"'
                )
                . '</td>';
            $o .= "\n" . '</tr>'. "\n" . '</table>';
            $o .= "\n" . tag('input type="hidden" name="filter" value="true"');
            $o .= "\n" . '</form>' . "\n" . '</div>';
            // Display table header
            $o .= "\n" . '<div>' . "\n"
                . '<form method="post" action="' . $sn . '?&amp;' . $plugin
                . '&amp;admin=plugin_main&amp;action=plugin_text">' . "\n"
                . '<table class="realblog_table" width="100%" cellpadding="0"'
                . ' cellspacing="0">';

            $o .= "\n" . '<tr>' . "\n"
                . '<td class="realblog_table_header" align="center">'
                . tag(
                    'input type="image" align="middle" src="'
                    . $plugin_images_folder . 'btn_delsel.png" name="batchdelete"'
                    . ' value="true" title="'
                    . $plugin_tx[$plugin]['tooltip_deleteall'] . '"'
                )
                . '</td>' . "\n"
                . '<td class="realblog_table_header" align="center">'
                . tag(
                    'input type="image" align="middle" src="' . $plugin_images_folder
                    . 'btn_status.png" name="changestatus" value="true"'
                    . ' title="' . $plugin_tx[$plugin]['tooltip_changestatus']
                    . '"'
                )
                . '</td>' . "\n"
                . '<td class="realblog_table_header" align="center">'
                . '<a href="' . $sn . '?&amp;' . $plugin
                . '&amp;admin=plugin_main&amp;action=add_realblog">'
                . tag(
                    'img src="' . $plugin_images_folder . 'btn_add.gif"'
                    . ' align="middle" title="'
                    . $plugin_tx[$plugin]['tooltip_add'] . '" alt=""'
                )
                . '</a></td>' . "\n"
                . '<td class="realblog_table_header" align="center">'
                . $plugin_tx[$plugin]['id_label'] . '</td>' . "\n"
                . '<td class="realblog_table_header" align="center">'
                . $plugin_tx[$plugin]['date_label'] . '</td>' . "\n"
                . '<td class="realblog_table_header" align="center">'
                . 'Status' . '</td>' . "\n"
                . '<td class="realblog_table_header" align="center">RSS Feed'
                . '</td>' . "\n"
                . '<td class="realblog_table_header" align="center">'
                . $plugin_tx['realblog']['comments_onoff'] . '</td>' . "\n"
                . '</tr>';

            $end_index = $page * $page_record_limit - 1;

            // Display table lines
            for ($record_index = $start_index; $record_index <= $end_index;
                 $record_index++
            ) {
                if ($record_index > $db_total_records - 1) {
                    $o .= '<tr>'
                        . '<td class="realblog_table_line" align="center">&nbsp;'
                        . '</td>'
                        . '<td class="realblog_table_line" align="center">&nbsp;'
                        . '</td>'
                        . '<td class="realblog_table_line" align="center">&nbsp;'
                        . '</td>'
                        . '<td class="realblog_table_line" align="center">&nbsp;'
                        . '</td>'
                        . '<td class="realblog_table_line" align="center">&nbsp;'
                        . '</td>'
                        . '<td class="realblog_table_line">&nbsp;</td>'
                        . '<td class="realblog_table_line">&nbsp;</td>'
                        . '<td class="realblog_table_line">&nbsp;</td>'
                        . '</tr>';
                } else {
                    $field = $records[$record_index];
                    $o .= '<tr>'
                        . '<td class="realblog_table_line" align="center">'
                        . tag(
                            'input type="checkbox" name="realblogtopics[]"'
                            . ' value="' . $field[REALBLOG_ID] . '"'
                        )
                        . '</td>'
                        . '<td class="realblog_table_line" valign="top"'
                        . ' align="center">'
                        . '<a href="' . $sn. '?&amp;' . $plugin
                        . '&amp;admin=plugin_main&amp;action=delete_realblog'
                        . '&amp;realblogID=' . $field[REALBLOG_ID] . '&amp;page='
                        . $page . '">'
                        . tag(
                            'img src="' . $plugin_images_folder . 'btn_delete.gif"'
                            . ' align="center" title="'
                            . $plugin_tx[$plugin]['tooltip_delete'] . '" alt=""'
                        )
                        . '</a></td>'
                        . '<td class="realblog_table_line" valign="top"'
                        . ' align="center">'
                        . '<a href="' . $sn . '?&amp;' . $plugin
                        . '&amp;admin=plugin_main&amp;action=modify_realblog'
                        . '&amp;realblogID=' . $field[REALBLOG_ID] . '&amp;page='
                        . $page . '">'
                        . tag(
                            'img src="' . $plugin_images_folder . 'btn_modify.gif"'
                            . ' align="center" title="'
                            . $plugin_tx[$plugin]['tooltip_modify'] . '" alt=""'
                        )
                        . '</a></td>'
                        . '<td class="realblog_table_line" valign="top"'
                        . ' align="center"><b>' . $field[REALBLOG_ID] . '</b></td>'
                        . '<td valign="top" style="text-align: center;"'
                        . ' class="realblog_table_line">'
                        . date(
                            $plugin_tx[$plugin]['date_format'], $field[REALBLOG_DATE]
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

            $o .= '</table></div>';
            $o .= tag('input type="hidden" name="page" value="' . $page . '"')
                . '</form><div>&nbsp;</div>';
            // Display table paging
            $tmp = ($db_total_records > 0)
                ? $plugin_tx[$plugin]['page_label'] . ' : ' . $page .  ' / '
                    . $page_total
                : '';
            $o .= '<div class="realblog_paging_block">'
                . '<div class="realblog_db_info">'
                . $plugin_tx[$plugin]['record_count'] . ' : '
                . $db_total_records . '</div>'
                . '<div class="realblog_page_info">&nbsp;&nbsp;&nbsp;' . $tmp
                . '</div>';

            if ($db_total_records > 0 && $page_total > 1) {
                if ($page_total > $page) {
                    $next = $page + 1;
                    $back = ($page > 1) ? ($next - 2) : '1';
                } else {
                    $next = $page_total;
                    $back = $page_total - 1;
                }
                $o .= '<div class="realblog_table_paging">'
                    . '<a href="' . $sn . '?&amp;' . $plugin
                    . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                    . $back . '&amp;filter1=' . $filter1 . '&amp;filter2='
                    . $filter2 . '&amp;filter3=' . $filter3 . '&amp;filter='
                    . $filter . '" title="'
                    . $plugin_tx[$plugin]['tooltip_previous'] . '">'
                    . tag(
                        'img src="' . $plugin_images_folder . 'btn_previous.gif"'
                        . ' alt=""'
                    )
                    . '</a>&nbsp;&nbsp;';
                for ($tt=1; $tt <= $page_total; $tt++) {
                    $separator = ($tt < $page_total) ? ' ' : '';
                    $o .= '<a href="' . $sn . '?&amp;' . $plugin
                        . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                        . $tt . '&amp;filter1=' . $filter1 . '&amp;filter2='
                        . $filter2 . '&amp;filter3=' . $filter3 . '&amp;filter='
                        . $filter . '" title="' . $plugin_tx[$plugin]['page_label']
                        . ' ' . $tt . '">[' . $tt . ']</a>' . $separator;
                }
                $o .= '&nbsp;&nbsp;<a href="' . $sn . '?&amp;' . $plugin
                    . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                    . $next . '&amp;filter1=' . $filter1 . '&amp;filter2='
                    . $filter2 . '&amp;filter3=' . $filter3 . '&amp;filter='
                    . $filter . '" title="' . $plugin_tx[$plugin]['tooltip_next']
                    . '">'
                    . tag(
                        'img src="' . $plugin_images_folder . 'btn_next.gif" alt=""'
                    )
                    . '</a>';
                $o .= '</div>';
            }
            $o .= '</div>';
        }
    } else {
        if ($admin == 'plugin_main') {
            $o .= '<h4>Plugin RealBlog</h4>'
                . '<p class="cmsimplecore_warning" style="text-align: center;">'
                . $plugin_tx['realblog']['message_datafile'] . '</p>';
        }
    }
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
    global $pth, $plugin, $plugin_cf, $plugin_tx, $sn, $db_name,
        $plugin_images_folder, $cf, $tx, $cal_format;

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
        $record = $db->selectUnique($db_name, REALBLOG_ID, $realblogID);
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

    $t = '<div class="realblog_fields_block"><div><h4>Realblog_XH '
        . REALBLOG_VERSION . ' : ' . $title . '</h4></div>';
    //$t.="<div>&nbsp;</div>";
    $t .= '<form name="realblog" method="post" action="' . $sn . '?&amp;'
        . $plugin . '&amp;admin=plugin_main">'
        . tag('input type="hidden" name="page" value="' . $ret_page . '"');
    $t .= '<table width="100%"><tr><td>'
        . tag(
            'input type="hidden" name="realblog_id" value="' . @$realblog_id . '"'
        )
        . '</td></tr>';
    $t .= '<tr><td width="30%"><span class="realblog_date_label">'
        . $plugin_tx[$plugin]['date_label'] . '</span></td>'
        . '<td width="5%">&nbsp;</td><td width="30%">'
        . '<span class="realblog_date_label">'
        . $plugin_tx[$plugin]['startdate_label'] . '</span></td>'
        . '<td width="5%">&nbsp;</td><td width="30%">'
        . '<span class="realblog_date_label">'
        . $plugin_tx[$plugin]['enddate_label'] . '</span></td></tr><tr>';
    $t .= '<td width="30%" valign="top">'
        . tag(
            'input type="text" name="realblog_date" id="date1" value="'
            . $realblog_date . '" size="10" maxlength="10" onfocus="this.blur()"'
        )
        . '&nbsp;'
        . tag(
            'img src="' . $plugin_images_folder . 'btn_calendar.gif"'
            . ' style="margin-left:1px;margin-bottom:-3px;"'
            . ' id="trig_date1" title="'
            . $plugin_tx[$plugin]['tooltip_datepicker'] . '" alt=""'
        )
        . '</td><td width="5%">&nbsp;</td>';
    $t .= '<td width="30%" valign="top">';

    // Auto publishing enabled/disabled by config variable
    if ($plugin_cf['realblog']['auto_publish'] == 'true') {
        $t .= tag(
            'input type="text" name="realblog_startdate" id="date2"'
            . ' value="' . $realblog_startdate . '" size="10" maxlength="10"'
            . ' onfocus="this.blur()"'
        );
        $t .= '&nbsp;'
            . tag(
                'img src="' . $plugin_images_folder . 'btn_calendar.gif"'
                . ' style="margin-left:1px;margin-bottom:-3px;"'
                . ' id="trig_date2" title="'
                . $plugin_tx[$plugin]['tooltip_datepicker'] . '" alt=""'
            );
    } else {
        $t .= $plugin_tx['realblog']['startdate_hint'];
    }

    $t .= '</td><td width="5%">&nbsp;</td>';
    $t .= '<td width="30%" valign="top">';

    // Auto archiving enabled/disabled by config variable
    if ($plugin_cf['realblog']['auto_archive'] == 'true') {
        $t .= tag(
            'input type="text" name="realblog_enddate" id="date3"'
            . ' value="' . $realblog_enddate . '" size="10" maxlength="10"'
            . ' onfocus="this.blur()"'
        );
        $t .= '&nbsp;'
            . tag(
                'img src="' . $plugin_images_folder . 'btn_calendar.gif"'
                . ' style="margin-left:1px;margin-bottom:-3px;"'
                . ' id="trig_date3" title="'
                . $plugin_tx[$plugin]['tooltip_datepicker'] . '" alt=""'
            );
    } else {
        $t .= $plugin_tx['realblog']['enddate_hint'];
    }

    $t .= '</td></tr><tr>';

    $t .= '<script type="text/javascript">'
        . 'Calendar.setup({inputField : "date1",ifFormat : "' . $cal_format
        . '",button : "trig_date1",align : "Br",singleClick : false,firstDay: 1,'
        . 'weekNumbers : false,electric:false,showsTime:false,timeFormat: "24"});';

    // Auto publishing enabled/disabled by config variable
    if ($plugin_cf['realblog']['auto_publish'] == 'true') {
        $t .= 'Calendar.setup({inputField : "date2",ifFormat : "' . $cal_format
        . '",button : "trig_date2",align : "Br",singleClick : true,firstDay: 1,'
        . 'weekNumbers : false,electric:false,showsTime:false,timeFormat: "24"});';
    }

    // Auto archiving enabled/disabled by config variable
    if ($plugin_cf['realblog']['auto_archive'] == 'true') {
        $t .= 'Calendar.setup({inputField : "date3",ifFormat : "' . $cal_format
            . '",button : "trig_date3",align : "Br",singleClick : false,firstDay: 1,'
            . 'weekNumbers:false,electric:false,showsTime:false,timeFormat: "24"});';
    }

    $t .= '</script>';

    $t .= '<td width="30%"><span class="realblog_date_label">'
        . $plugin_tx[$plugin]['status_label']
        . '</span></td><td width="5%">&nbsp;</td><td width="30%">&nbsp;</span></td>'
        . '<td width="5%">&nbsp;</td><td width="30%"><span>&nbsp;</span></td></tr>'
        . '<tr>';
    $t .= '<td width="30%" valign="top">'
        . '<select name="realblog_status">'
        . '<option value="0" ' . @$status[0] . '>'
        . $plugin_tx[$plugin]['readyforpublishing'] . '</option>'
        . '<option value="1" ' . @$status[1] . '>'
        . $plugin_tx[$plugin]['published'] . '</option>'
        . '<option value="2" ' . @$status[2] . '>'
        . $plugin_tx[$plugin]['archived'] . '</option>'
        . '<option value="3" ' . @$status[3] . '>'
        . $plugin_tx[$plugin]['backuped'] . '</option>'
        . '</select></td>';
    $t .= '<td width="5%">&nbsp;</td><td width="30%" valign="top">'
        . tag('input type="checkbox" name="realblog_comments" ' . @$commentschecked)
        . '&nbsp;<span>' . $plugin_tx[$plugin]['comment_label'] . '</td>';
    $t .= '<td width="5%">&nbsp;</td><td width="30%" valign="top">'
        . tag('input type="checkbox" name="realblog_rssfeed" ' . @$rsschecked)
        . '&nbsp;<span>' . $plugin_tx[$plugin]['rss_label'] . '</span></td></tr>';

    $t .= '<tr><td width="30%"><h4>' . $plugin_tx[$plugin]['title_label']
        . '</h4></td></tr>';
    $t .= '<tr><td colspan="5">'
        . tag(
            'input type="text" value="' . @$realblog_title
            . '" name="realblog_title" size="70"'
        )
        . '</td></tr>';

    $t .= '<tr><td width="30%" style="padding-bottom: 8px;"><h4>'
        . $plugin_tx[$plugin]['headline_label'] . '</h4>'
        . '<p><b>Script for copy & paste:</b></p>'
        . '{{{PLUGIN:rbCat(\'|the_category|\');}}}'
        . '</td></tr>';
    $t .= '<tr><td colspan="5" style="padding-bottom: 8px;">'
        . '<textarea class="realblog_headline_field" name="realblog_headline"'
        . ' id="realblog_headline" rows="6" cols="60">'
        . htmlspecialchars(@$realblog_headline) . '</textarea></td></tr>';

    $t .= '<tr><td colspan="5" style="padding-bottom: 8px;"><h4>'
        . $plugin_tx[$plugin]['story_label'] . '</h4>'
        . '<p><b>Script for copy & paste:</b></p>'
        . '{{{PLUGIN:CommentsMembersOnly();}}}'
        . '</td></tr>';
    $t .= '<tr><td colspan="5"><textarea class="realblog_story_field"'
         . ' name="realblog_story" id="realblog_story" rows="30" cols="80">'
         . htmlspecialchars(@$realblog_story) . '</textarea></td></tr>';

    switch ($action) {
    case 'add_realblog':
        $t .= '<tr><td>&nbsp;'
            . tag('input type="hidden" name="do" value="add"')
            . '</td></tr>';
        $button_label = $plugin_tx[$plugin]['btn_add'];
        break;
    case 'modify_realblog':
        $t .= '<tr><td>&nbsp;'
            . tag('input type="hidden" name="do" value="modify"')
            . '</td></tr>';
        $button_label = $plugin_tx[$plugin]['btn_modify'];
        break;
    case 'delete_realblog':
        $t .= '<tr><td>&nbsp;'
            . tag('input type="hidden" name="do" value="delete"')
            . '</td></tr>';
        $button_label = $plugin_tx[$plugin]['btn_delete'];
        break;
    }

    $t .= '<tr><td colspan="5" align="center">'
        . tag(
            'input type="submit" name="save" value="'
            . $button_label . '"'
        )
        . '&nbsp;&nbsp;&nbsp;'
        . tag(
            'input type="button" name="cancel" value="'
            . $plugin_tx[$plugin]['btn_cancel'] . '" onclick=\'location.href="'
            . $sn . '?&amp;' . $plugin . '&amp;admin=plugin_main'
            . '&amp;action=plugin_text&page=' . $ret_page . '"\''
        )
        . '</td></tr>';
    $t .= '</table></form>';
    $t .= '</div>';
    return $t;
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
 * @global array  The localization of the plugins.
 * @global string The name of the current plugin.
 * @global string The script name.
 */
function Realblog_dbconfirm($title, $info, $page)
{
    // Display a confirmation
    global $plugin_tx, $plugin, $sn;

    if (!isset($page)) {
        $page = $_SESSION['page'];
    }

    $t = '<div><h4>Realblog_XH ' . REALBLOG_VERSION . ' : ' . $title . '</h4></div>';
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
