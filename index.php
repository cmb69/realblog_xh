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
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
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
 * The flatfile database.
 */
require_once $pth['folder']['plugin_classes'] . 'flatfile.php';

/**
 * General utility functions.
 */
require_once $pth['folder']['plugin'] . 'constants.php';

/**
 * Backward compatibility.
 */
require_once $pth['folder']['plugin'] . 'compat.php';

/**
 * The plugin version.
 */
define('REALBLOG_VERSION', '@REALBLOG_VERSION@');

/**
 * The plugin controller.
 *
 * @var Realblog_Controller
 */
$_Realblog_controller = new Realblog_Controller();

/**
 * Displays the realblog's topic with status = published.
 *
 * @param array  $showSearch  Whether to show the searchform.
 * @param string $realBlogCat FIXME
 *
 * @return string (X)HTML.
 *
 * @global Realblog_Controller The plugin controller.
 */
function Realblog_blog($showSearch = false, $realBlogCat = 'all')
{
    global $_Realblog_controller;

    return $_Realblog_controller->blog($showSearch, $realBlogCat);
}

/**
 * Displays the archived realblog topics.
 *
 * @param mixed $showSearch Whether to show the search form.
 *
 * @return string (X)HTML.
 *
 * @global Realblog_Controller The plugin controller.
 */
function Realblog_archive($showSearch = false)
{
    global $_Realblog_controller;

    return $_Realblog_controller->archive($showSearch);
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
 * @global Realblog_Controller The plugin controller.
 */
function Realblog_link($pageUrl)
{
    global $_Realblog_controller;

    return $_Realblog_controller->link($pageUrl);
}

/**
 * Returns a graphical hyperlink to the RSS feed.
 *
 * @return string (X)HTML.
 *
 * @global Realblog_Controller The plugin controller.
 */
function Realblog_feedLink()
{
    global $_Realblog_controller;

    return $_Realblog_controller->feedLink();
}

$_Realblog_controller->init();

?>
