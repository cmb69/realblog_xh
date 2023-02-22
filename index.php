<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2023 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
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

use Realblog\Dic;
use Realblog\Infra\Request;
use Realblog\Plugin;

/**
 * @var array<string,array<string,string>> $pth
 */

require_once $pth['folder']['plugin'] . 'compat.php';

Plugin::init();

function realblog_blog(bool $showSearch = false, string $category = "all"): string
{
    return Dic::makeBlogController()(new Request, $showSearch, $category);
}

function realblog_archive(bool $showSearch = false): string
{
    return Dic::makeArchiveController()(new Request, $showSearch);
}

function realblog_link(string $pageUrl, bool $showTeaser = false): string
{
    return Dic::makeLinkController()(new Request, $pageUrl, $showTeaser);
}

function realblog_mostpopular(string $pageUrl): string
{
    return Dic::makeMostPopularController()(new Request, $pageUrl);
}

function realblog_feedlink(string $target = "_self"): string
{
    return Dic::makeFeedLinkController()(new Request, $target);
}
