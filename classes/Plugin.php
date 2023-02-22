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

namespace Realblog;

use Realblog\Infra\Request;

class Plugin
{
    const VERSION = '3.0beta9';

    /**
     * @return void
     */
    public static function init()
    {
        global $plugin_cf;

        if ($plugin_cf['realblog']['auto_publish']) {
            self::autoPublish();
        }
        if ($plugin_cf['realblog']['auto_archive']) {
            self::autoArchive();
        }
        if ($plugin_cf['realblog']['rss_enabled']) {
            self::emitAlternateRSSLink();
            if (preg_match('/^rss$/', $_GET['realblog_feed'] ?? "")) {
                header('Content-Type: application/rss+xml; charset=UTF-8');
                echo Dic::makeFeedController()(new Request)->fire();
                exit;
            }
        }
    }

    /**
     * @return void
     * @global string $hjs
     */
    private static function emitAlternateRSSLink()
    {
        global $hjs;

        $hjs .= '<link rel="alternate" type="application/rss+xml"'
            . ' href="./?realblog_feed=rss">';
    }

    /**
     * @return void
     */
    private static function autoPublish()
    {
        Dic::makeDb()->autoChangeStatus('publishing_date', 1);
    }

    /**
     * @return void
     */
    private static function autoArchive()
    {
        Dic::makeDb()->autoChangeStatus('archiving_date', 2);
    }

    /**
     * @return int
     */
    public static function getPage()
    {
        global $edit;

        if (defined("XH_ADM") && XH_ADM && $edit) {
            if (isset($_GET['realblog_page'])) {
                $page = max((int) ($_GET['realblog_page'] ?? 1), 1);
                $_COOKIE['realblog_page'] = $page;
                setcookie('realblog_page', (string) $page, 0, CMSIMPLE_ROOT);
            } else {
                $page = max((int) ($_COOKIE['realblog_page'] ?? 1), 1);
            }
        } else {
            $page = max((int) ($_GET['realblog_page'] ?? 1), 1);
        }
        return $page;
    }

    /**
     * @param int $num
     * @return bool
     */
    public static function getFilter($num)
    {
        $varname = "realblog_filter$num";
        if (isset($_GET[$varname])) {
            $filter = (bool) ($_GET[$varname] ?? false);
            $_COOKIE[$varname] = $filter ? 'on' : '';
            setcookie($varname, $filter ? 'on' : '', 0, CMSIMPLE_ROOT);
        } else {
            $filter = (bool) ($_COOKIE[$varname] ?? false);
        }
        return $filter;
    }
}
