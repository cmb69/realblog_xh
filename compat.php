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

/**
 * @param string $options
 * @param string $category
 * @return string
 * @global Realblog\Controller $_Realblog_controller
 * @deprecated as of 3.0beta6
 */
function showrealblog($options = null, $category = 'all')
{
    global $_Realblog_controller;

    trigger_error('Function ' . __FUNCTION__ . '() is deprecated', E_USER_DEPRECATED);
    $includesearch = false;
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['showsearch'])) {
        switch (strtolower($arguments['showsearch'])) {
            case '0':
            case 'false':
                $includesearch = false;
                break;
            case '1':
            case 'true':
                $includesearch = true;
                break;
        }
    }
    return $_Realblog_controller->blog($includesearch, $category);
}

/**
 * @param string $options
 * @return string
 * @global Realblog\Controller $_Realblog_controller
 * @deprecated as of 3.0beta6
 */
function showrealblogarchive($options = null)
{
    global $_Realblog_controller;

    trigger_error('Function ' . __FUNCTION__ . '() is deprecated', E_USER_DEPRECATED);
    $includesearch = false;
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['showsearch'])) {
        $argument = strtolower($arguments['showsearch']);
        switch ($argument) {
            case '0':
            case 'false':
                $includesearch = false;
                break;
            case '1':
            case 'true':
                $includesearch = true;
                break;
        }
    }
    return $_Realblog_controller->archive($includesearch);
}

/**
 * @param string $options
 * @return string
 * @global Realblog\Controller $_Realblog_controller
 * @deprecated as of 3.0beta6
 */
function realbloglink($options)
{
    global $_Realblog_controller;

    trigger_error('Function ' . __FUNCTION__ . '() is deprecated', E_USER_DEPRECATED);
    $realblog_page = '';
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['realblogpage'])) {
        $realblog_page = $arguments['realblogpage'];
    }
    return $_Realblog_controller->link($realblog_page);
}

/**
 * @param string $arguments
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
 * @return string
 * @global Realblog\Controller $_Realblog_controller
 * @deprecated as of 3.0beta6
 */
function realblog_rss_adv()
{
    global $_Realblog_controller;

    trigger_error('Function ' . __FUNCTION__ . '() is deprecated', E_USER_DEPRECATED);
    return $_Realblog_controller->feedLink();
}

/**
 * @return void
 * @deprecated as of 3.0beta6
 */
function rbCat()
{
    trigger_error('Function ' . __FUNCTION__ . '() is deprecated', E_USER_DEPRECATED);
}

/**
 * @return void
 * @deprecated as of 3.0beta4
 */
function commentsMembersOnly()
{
    trigger_error('Function ' . __FUNCTION__ . '() is deprecated', E_USER_DEPRECATED);
}
