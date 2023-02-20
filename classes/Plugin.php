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

use XH\CSRFProtection as CsrfProtector;

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
            $rssFeedRequested = filter_input(
                INPUT_GET,
                'realblog_feed',
                FILTER_VALIDATE_REGEXP,
                array('options' => array('regexp' => '/^rss$/'))
            );
            if ($rssFeedRequested) {
                header('Content-Type: application/rss+xml; charset=UTF-8');
                echo Dic::makeFeedController()();
                exit;
            }
        }
        if (defined("XH_ADM") && XH_ADM) {
            self::registerPluginMenu();
            if (XH_wantsPluginAdministration('realblog')) {
                self::handleAdministration();
            }
        }
    }

    /**
     * @return void
     */
    private static function registerPluginMenu()
    {
        global $sn, $plugin_tx;

        XH_registerStandardPluginMenuItems(true);
        XH_registerPluginMenuItem(
            'realblog',
            $plugin_tx['realblog']['exchange_heading'],
            "$sn?realblog&admin=data_exchange"
        );
    }

    /**
     * @return void
     */
    private static function handleAdministration()
    {
        global $sn, $admin, $action, $o, $plugin_tx, $_XH_csrfProtection;

        assert($_XH_csrfProtection instanceof CsrfProtector);
        $o .= print_plugin_admin('on');
        pluginMenu('ROW');
        pluginMenu('TAB', "$sn?realblog&admin=data_exchange", '', $plugin_tx['realblog']['exchange_heading']);
        $o .= pluginMenu('SHOW');
        switch ($admin) {
            case '':
                $o .= Dic::makeInfoController()();
                break;
            case 'plugin_main':
                $o .= Dic::makeMainAdminController()($action)->trigger();
                break;
            case 'data_exchange':
                $o .= Dic::makeDataExchangeController()($action)->trigger();
                break;
            default:
                $o .= plugin_admin_common();
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
            if (filter_has_var(INPUT_GET, 'realblog_page')) {
                $page = filter_input(
                    INPUT_GET,
                    'realblog_page',
                    FILTER_VALIDATE_INT,
                    array('options' => array('min_range' => 1))
                );
                $_COOKIE['realblog_page'] = $page;
                setcookie('realblog_page', $page, 0, CMSIMPLE_ROOT);
            } else {
                $page = filter_input(
                    INPUT_COOKIE,
                    'realblog_page',
                    FILTER_VALIDATE_INT,
                    array('options' => array('min_range' => 1, 'default' => 1))
                );
            }
        } else {
            $page = filter_input(
                INPUT_GET,
                'realblog_page',
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1, 'default' => 1))
            );
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
        if (filter_has_var(INPUT_GET, $varname)) {
            $filter = filter_input(INPUT_GET, $varname, FILTER_VALIDATE_BOOLEAN);
            $_COOKIE[$varname] = $filter ? 'on' : '';
            setcookie($varname, $filter ? 'on' : '', 0, CMSIMPLE_ROOT);
        } else {
            $filter = filter_input(INPUT_COOKIE, $varname, FILTER_VALIDATE_BOOLEAN);
        }
        return $filter;
    }

    /**
     * @param string $pageUrl
     * @param array<string,string> $params
     * @return string
     */
    public static function url($pageUrl, $params = array())
    {
        global $sn;

        $replacePairs = array(
            //'realblog_id' => 'id',
            //'realblog_page' => 'page'
        );
        $url = $sn . '?' . $pageUrl;
        ksort($params);
        foreach ($params as $name => $value) {
            if (!($name == 'realblog_page' && $value == 1
                || $name == 'realblog_year' && $value == date('Y')
                || $name == 'realblog_search' && !$value)
            ) {
                $url .= '&' . strtr($name, $replacePairs) . '=' . $value;
            }
        }
        return $url;
    }
}
