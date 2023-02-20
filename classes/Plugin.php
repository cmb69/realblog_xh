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

use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Infra\ScriptEvaluator;
use Realblog\Infra\View;
use ReflectionClass;
use XH\CSRFProtection as CsrfProtector;

class Plugin
{
    const VERSION = '3.0beta9';

    /**
     * @return void
     */
    public static function init()
    {
        global $pth, $sn, $su, $plugin_cf, $plugin_tx;

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
                $controller = new FeedController(
                    "{$pth['folder']['plugins']}realblog/",
                    $pth['folder']['images'],
                    $plugin_cf['realblog'],
                    $plugin_tx['realblog'],
                    $sn,
                    new Finder(self::getDb()),
                    new ScriptEvaluator()
                );
                header('Content-Type: application/rss+xml; charset=UTF-8');
                echo $controller->defaultAction();
                exit;
            }
        }
        if (defined("XH_ADM") && XH_ADM) {
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
        $class = new ReflectionClass(self::class);
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
            eval("function Realblog_$name($paramString) {return \\Realblog\\Plugin::{$name}Command($argString);}");
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
        global $pth, $sn, $sl, $admin, $action, $o, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        assert($_XH_csrfProtection instanceof CsrfProtector);
        $o .= print_plugin_admin('on');
        pluginMenu('ROW');
        pluginMenu('TAB', "$sn?realblog&admin=data_exchange", '', $plugin_tx['realblog']['exchange_heading']);
        $o .= pluginMenu('SHOW');
        $methodName = lcfirst(implode('', array_map('ucfirst', explode('_', $action)))) . 'Action';
        switch ($admin) {
            case '':
                $controller = new InfoController(
                    "{$pth['folder']['plugins']}realblog/",
                    $plugin_cf['realblog'],
                    new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog'])
                );
                $o .= $controller->defaultAction();
                break;
            case 'plugin_main':
                $controller = new MainAdminController(
                    "{$pth['folder']['plugins']}realblog/",
                    $plugin_cf['realblog'],
                    $plugin_tx['realblog'],
                    $sn,
                    $sl,
                    self::getDb(),
                    new Finder(self::getDb()),
                    $_XH_csrfProtection,
                    new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog']),
                    new Editor(),
                    time()
                );
                if (method_exists($controller, $methodName)) {
                    $o .= $controller->{$methodName}()->trigger();
                } else {
                    $o .= $controller->defaultAction()->trigger();
                }
                break;
            case 'data_exchange':
                $controller = new DataExchangeController(
                    "{$pth['folder']['content']}realblog/",
                    $plugin_tx['realblog'],
                    $sn,
                    self::getDb(),
                    new Finder(self::getDb()),
                    $_XH_csrfProtection,
                    new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog'])
                );
                if (method_exists($controller, $methodName)) {
                    $o .= $controller->{$methodName}()->trigger();
                } else {
                    $o .= $controller->defaultAction()->trigger();
                }
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
        self::getDb()->autoChangeStatus('publishing_date', 1);
    }

    /**
     * @return void
     */
    private static function autoArchive()
    {
        self::getDb()->autoChangeStatus('archiving_date', 2);
    }

    /**
     * @param bool $showSearch
     * @param string $category
     * @return string
     */
    public static function blogCommand($showSearch = false, $category = 'all')
    {
        global $pth, $plugin_cf, $plugin_tx;

        $controller = new BlogController(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            $showSearch,
            self::getDb(),
            new Finder(self::getDb()),
            new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog']),
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
        global $pth, $plugin_cf, $plugin_tx;

        $controller = new ArchiveController(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            $showSearch,
            self::getDb(),
            new Finder(self::getDb()),
            new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog'])
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
        global $pth, $plugin_cf, $plugin_tx, $u;

        $controller = new LinkController(
            $plugin_cf['realblog'],
            $plugin_tx['realblog'],
            $pageUrl,
            $u,
            $showTeaser,
            new Finder(self::getDb()),
            new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog']),
            new ScriptEvaluator()
        );
        return $controller->defaultAction();
    }

    /**
     * @param string $pageUrl
     * @return string
     */
    public static function mostPopularCommand($pageUrl)
    {
        global $pth, $plugin_cf, $plugin_tx, $u;

        $controller = new MostPopularController(
            $plugin_cf['realblog'],
            $pageUrl,
            $u,
            new Finder(self::getDb()),
            new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog'])
        );
        return $controller->defaultAction();
    }

    /**
     * @param string $target
     * @return string
     */
    public static function feedLinkCommand($target = '_self')
    {
        global $pth, $plugin_tx, $sn;

        $controller = new FeedLinkController(
            "{$pth['folder']['plugin']}realblog/",
            $plugin_tx['realblog'],
            $sn
        );
        return $controller->defaultAction($target);
    }

    /**
     * @return DB
     */
    private static function getDb()
    {
        static $db = null;
        global $pth;

        $filename = "{$pth['folder']['content']}realblog/realblog.db";
        if ($db === null) {
            $db = new DB($filename);
        }
        return $db;
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
