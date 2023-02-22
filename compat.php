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

use Realblog\Dic;
use Realblog\Infra\Request;

/**
 * @param string $options
 * @param string $category
 * @return string
 * @deprecated as of 3.0beta6
 */
function showrealblog($options = "", $category = 'all')
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; use Realblog_blog() instead", E_USER_DEPRECATED);
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
    return Dic::makeBlogController()(new Request, $includesearch, $category);
}

/**
 * @param string $options
 * @return string
 * @deprecated as of 3.0beta6
 */
function showrealblogarchive($options = "")
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; use Realblog_archive() instead", E_USER_DEPRECATED);
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
    return Dic::makeArchiveController()(new Request, $includesearch);
}

/**
 * @param string $options
 * @return string
 * @deprecated as of 3.0beta6
 */
function realbloglink($options)
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; use Realblog_link() instead", E_USER_DEPRECATED);
    $realblog_page = '';
    $arguments = Realblog_getArguments($options);
    if (isset($arguments['realblogpage'])) {
        $realblog_page = $arguments['realblogpage'];
    }
    return Dic::makeLinkController()(new Request, $realblog_page);
}

/**
 * @param string $arguments
 * @return array<string,string>
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
 * @deprecated as of 3.0beta6
 */
function realblog_rss_adv()
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; use Realblog_feedLink() instead", E_USER_DEPRECATED);
    return Dic::makeFeedLinkController()(new Request, "_self");
}

/**
 * @return void
 * @deprecated as of 3.0beta6
 */
function rbCat()
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; enter the categories in the respective field instead", E_USER_DEPRECATED);
}

/**
 * @return void
 * @deprecated as of 3.0beta4
 */
function commentsMembersOnly()
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; this functionality is not yet supported", E_USER_DEPRECATED);
}
