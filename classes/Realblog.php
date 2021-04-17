<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
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

use stdClass;
use ReflectionClass;

class Realblog
{
    const VERSION = '3.0beta9';

    /**
     * @return void
     */
    public static function init()
    {
        global $su, $plugin_cf, $plugin_tx;

        self::registerCommands();
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
                $controller = new FeedController($plugin_cf['realblog'], $plugin_tx['realblog'], new Finder());
                echo $controller->defaultAction();
                exit;
            }
        }
        /** @psalm-suppress UndefinedConstant */
        if (XH_ADM) {
            self::registerPluginMenu();
            if (XH_wantsPluginAdministration('realblog') || $su === 'realblog') {
                self::handleAdministration();
            }
        }
    }

    /**
     * @return void
     */
    private static function registerCommands()
    {
        $class = new ReflectionClass(Realblog::class);
        $commands = array();
        foreach ($class->getMethods() as $method) {
            $methodName = $method->getName();
            if (preg_match('/.*(?=Command$)/', $methodName, $m)) {
                $commands[$m[0]] = $method->getParameters();
            }
        }
        foreach ($commands as $name => $params) {
            $paramList = $argList = array();
            foreach ($params as $param) {
                $string = '$' . $param->getName();
                $argList[] = $string;
                if ($param->isOptional()) {
                    $string .= '=' . var_export($param->getDefaultValue(), true);
                }
                $paramList[] = $string;
            }
            $paramString = implode(',', $paramList);
            $argString = implode(',', $argList);
            eval("function Realblog_$name($paramString) {return \\Realblog\\Realblog::{$name}Command($argString);}");
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
        global $sn, $admin, $o, $plugin_tx;

        $o .= print_plugin_admin('on');
        pluginMenu('ROW');
        pluginMenu('TAB', "$sn?realblog&admin=data_exchange", '', $plugin_tx['realblog']['exchange_heading']);
        $o .= pluginMenu('SHOW');
        switch ($admin) {
            case '':
                $o .= (new InfoController(new View()))->defaultAction();
                break;
            case 'plugin_main':
                self::routeTo(MainAdminController::class);
                break;
            case 'data_exchange':
                self::routeTo(DataExchangeController::class);
                break;
            default:
                $o .= plugin_admin_common();
        }
    }

    /**
     * @param class-string $controllerClassName
     * @return void
     */
    private static function routeTo($controllerClassName)
    {
        global $o, $action, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        $methodName = lcfirst(implode('', array_map('ucfirst', explode('_', $action)))) . 'Action';
        $controller = new $controllerClassName(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            new Finder(),
            $_XH_csrfProtection,
            new View()
        );
        $class = new ReflectionClass($controller);
        if ($class->hasMethod($methodName)
            && ($method = $class->getMethod($methodName))
            && $method->isPublic()
        ) {
            $o .= $method->invoke($controller);
        } else {
            $o .= $controller->defaultAction();
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
        DB::autoChangeStatus('publishing_date', 1);
    }

    /**
     * @return void
     */
    private static function autoArchive()
    {
        DB::autoChangeStatus('archiving_date', 2);
    }

    /**
     * @param bool $showSearch
     * @param string $category
     * @return string
     */
    public static function blogCommand($showSearch = false, $category = 'all')
    {
        global $plugin_cf, $plugin_tx;

        $controller = new BlogController(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            $showSearch,
            new Finder(),
            new View(),
            $category
        );
        if (filter_has_var(INPUT_GET, 'realblog_id')) {
            return (string) $controller->showArticleAction(filter_input(
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
     * @param bool $showSearch
     * @return string
     */
    public static function archiveCommand($showSearch = false)
    {
        global $plugin_cf, $plugin_tx;

        $controller = new ArchiveController(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            $showSearch,
            new Finder(),
            new View()
        );
        if (filter_has_var(INPUT_GET, 'realblog_id')) {
            return (string) $controller->showArticleAction(filter_input(
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
     * @param string $pageUrl
     * @param bool $showTeaser
     * @return string
     */
    public static function linkCommand($pageUrl, $showTeaser = false)
    {
        global $plugin_cf, $plugin_tx;

        $controller = new LinkController(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            $pageUrl,
            $showTeaser,
            new Finder(),
            new View()
        );
        return $controller->defaultAction();
    }

    /**
     * @param string $pageUrl
     * @return string
     */
    public static function mostPopularCommand($pageUrl)
    {
        global $plugin_cf, $plugin_tx;

        $controller = new MostPopularController(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            $pageUrl,
            new Finder(),
            new View()
        );
        return $controller->defaultAction();
    }

    /**
     * @param string $target
     * @return string
     */
    public static function feedLinkCommand($target = '_self')
    {
        global $plugin_cf, $plugin_tx;

        $controller = new FeedLinkController($plugin_cf['realblog'], $plugin_tx['realblog']);
        return $controller->defaultAction($target);
    }

    /**
     * @return int
     */
    public static function getPage()
    {
        global $edit;

        /** @psalm-suppress UndefinedConstant */
        if (XH_ADM && $edit) {
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
     * @param array  $params
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
