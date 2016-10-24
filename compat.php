<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

/**
 * @deprecated as of 3.0beta6
 */
function showrealblog($options = null, $category = 'all')
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
    return Realblog_blog($includesearch, $category);
}

/**
 * @deprecated as of 3.0beta6
 */
function showrealblogarchive($options = null)
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
    return Realblog_archive($includesearch);
}

/**
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
    return Realblog_link($realblog_page);
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
 * @deprecated as of 3.0beta6
 */
function realblog_rss_adv()
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; use Realblog_feedLink() instead", E_USER_DEPRECATED);
    return Realblog_feedLink();
}

/**
 * @deprecated as of 3.0beta6
 */
function rbCat()
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; enter the categories in the respective field instead", E_USER_DEPRECATED);
}

/**
 * @deprecated as of 3.0beta4
 */
function commentsMembersOnly()
{
    $function = __FUNCTION__;
    trigger_error("$function() is deprecated; this functionality is not yet supported", E_USER_DEPRECATED);
}
