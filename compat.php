<?php

/**
 * Backward compatibility.
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
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * Renders the published articles.
 *
 * @param string $options     An option string (options: showsearch).
 * @param string $realBlogCat A category.
 *
 * @return string (X)HTML.
 *
 * @global Realblog_Controller The plugin controller.
 */
function showrealblog($options = null, $realBlogCat = 'all')
{
    global $_Realblog_controller;

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
    return $_Realblog_controller->blog($includesearch, $realBlogCat);
}

/**
 * Renders the archived articles.
 *
 * @param string $options An option string (options: showsearch).
 *
 * @return string (X)HTML.
 *
 * @global Realblog_Controller The plugin controller.
 */
function showrealblogarchive($options = null)
{
    global $_Realblog_controller;

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
 * Renders the published topics with a link to the blog page from the template.
 *
 * @param string $options An option string (options: realblogpage).
 *
 * @return string (X)HTML.
 *
 * @global Realblog_Controller The plugin controller.
 */
function realbloglink($options)
{
    global $_Realblog_controller;

    $realblog_page = '';
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['realblogpage'])) {
        $realblog_page = $arguments['realblogpage'];
    }
    return $_Realblog_controller->link($realblog_page);
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
 * Renders a hyperlink to the newsfeed.
 *
 * @return string (X)HTML.
 *
 * @global Realblog_Controller The plugin controller.
 */
function realblog_rss_adv()
{
    global $_Realblog_controller;

    return $_Realblog_controller->feedLink();
}

/**
 * A dummy function for categories.
 *
 * @return void
 */
function rbCat()
{
    return;
}

/**
 * Dummy function for compatibility reasons.
 *
 * @return void
 *
 * @deprecated since 3.0beta4
 */
function commentsMembersOnly()
{
    // should be E_USER_DEPRECATED, but that requires PHP >= 5.3 or XH >= 1.6.3
    trigger_error('Function ' . __FUNCTION__ . '() is deprecated', E_USER_NOTICE);
}

?>
