<?php

/**
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

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

require_once $pth['folder']['plugin'] . 'compat.php';

class_alias('\Realblog\CommentsBridge', 'Realblog_CommentsBridge');

/**
 * Displays the realblog's topic with status = published.
 */
function Realblog_blog($showSearch = false, $category = 'all')
{
    $controller = new Realblog\BlogController($showSearch, $category);
    if (filter_has_var(INPUT_GET, 'realblog_id')) {
        return $controller->showArticleAction(filter_input(
            INPUT_GET,
            'realblog_id',
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        ));
    } else {
        return $controller->defaultAction();
    }
}

/**
 * Displays the archived realblog topics
 */
function Realblog_archive($showSearch = false)
{
    $controller = new Realblog\ArchiveController($showSearch);
    if (filter_has_var(INPUT_GET, 'realblog_id')) {
        return $controller->showArticleAction(filter_input(
            INPUT_GET,
            'realblog_id',
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        ));
    } else {
        return $controller->defaultAction();
    }
}

/**
 * Displays the realblog topics with a link to the blog page from the template
 */
function Realblog_link($pageUrl, $showTeaser = false)
{
    $controller = new Realblog\LinkController($pageUrl, $showTeaser);
    return $controller->defaultAction();
}

/**
 * @param string $pageUrl
 * @return string
 */
function Realblog_mostPopular($pageUrl)
{
    $controller = new Realblog\MostPopularController($pageUrl);
    return $controller->defaultAction();
}

/**
 * Returns a graphical hyperlink to the RSS feed
 */
function Realblog_feedLink($target = "_self")
{
    $controller = new Realblog\FeedLinkController();
    return $controller->defaultAction($target);
}

Realblog\Realblog::init();
