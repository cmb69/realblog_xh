<?php

/**
 * @category  CMSimple_XH
 * @package   Realblog
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;
use ReflectionClass;

class Realblog
{
    const VERSION = '@REALBLOG_VERSION@';

    /**
     * @return void
     * @global array $plugin_cf
     */
    public static function init()
    {
        global $plugin_cf;

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
                self::deliverFeed();
            }
        }
        if (defined('XH_ADM') && XH_ADM) {
            self::registerPluginMenu();
            if (self::isAdministrationRequested()) {
                self::handleAdministration();
            }
        }
    }

    private static function registerCommands()
    {
        $class = new ReflectionClass('\Realblog\Realblog');
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

    private static function registerPluginMenu()
    {
        global $sn, $plugin_tx;

        if (function_exists('XH_registerStandardPluginMenuItems')) {
            XH_registerStandardPluginMenuItems(true);
        }
        if (function_exists('XH_registerPluginMenuItem')) {
            XH_registerPluginMenuItem(
                'realblog',
                $plugin_tx['realblog']['exchange_heading'],
                "$sn?realblog&admin=data_exchange"
            );
        }
    }

    /**
     * @return bool
     * @global string $realblog
     */
    private static function isAdministrationRequested()
    {
        global $realblog, $su;

        return isset($realblog) && $realblog == 'true' || $su === 'realblog';
    }

    /**
     * @return void
     */
    private static function handleAdministration()
    {
        global $sn, $admin, $action, $o, $plugin_tx;

        $o .= print_plugin_admin('on');
        pluginMenu('ROW');
        pluginMenu('TAB', "$sn?realblog&admin=data_exchange", '', $plugin_tx['realblog']['exchange_heading']);
        $o .= pluginMenu('SHOW');
        switch ($admin) {
            case '':
                $o .= self::renderInfoView();
                break;
            case 'plugin_main':
                self::routeTo('\Realblog\MainAdminController');
                break;
            case 'data_exchange':
                self::routeTo('\Realblog\DataExchangeController');
                break;
            default:
                $o .= plugin_admin_common($action, $admin, 'realblog');
        }
    }

    private static function renderInfoView()
    {
        global $pth;

        $view = new View('info');
        $view->logoPath = "{$pth['folder']['plugins']}realblog/realblog.png";
        $view->version = Realblog::VERSION;
        $systemCheck = new SystemCheck();
        return $view->render() . $systemCheck->render();
    }

    private static function routeTo($controllerClassName)
    {
        global $o, $action;

        $methodName = lcfirst(implode('', array_map('ucfirst', explode('_', $action)))) . 'Action';
        $controller = new $controllerClassName();
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

        $hjs .= tag(
            'link rel="alternate" type="application/rss+xml"'
            . ' href="./?realblog_feed=rss"'
        );
    }

    /**
     * @return void
     */
    private static function deliverFeed()
    {
        global $sn, $pth, $plugin_cf, $plugin_tx;

        header('Content-Type: application/rss+xml; charset=UTF-8');
        $view = new View('feed');
        $view->url = CMSIMPLE_URL . '?' . $plugin_tx['realblog']['rss_page'];
        $view->managingEditor = $plugin_cf['realblog']['rss_editor'];
        $view->hasLogo = (bool) $plugin_cf['realblog']['rss_logo'];
        $view->imageUrl = preg_replace(
            array('/\/[^\/]+\/\.\.\//', '/\/\.\//'),
            '/',
            CMSIMPLE_URL . $pth['folder']['images']
            . $plugin_cf['realblog']['rss_logo']
        );
        $count = $plugin_cf['realblog']['rss_entries'];
        $view->articles = Finder::findFeedableArticles($count);
        $view->articleUrl = function ($article) use ($sn, $plugin_tx) {
            return CMSIMPLE_URL . substr(
                Realblog::url(
                    $plugin_tx['realblog']["rss_page"],
                    array('realblog_id' => $article->id)
                ),
                strlen($sn)
            );
        };
        $view->evaluatedTeaser = function ($article) {
            return evaluate_scripting($article->teaser);
        };
        $view->rssDate = function ($article) {
            return date('r', $article->date);
        };
        echo '<?xml version="1.0" encoding="UTF-8"?>', PHP_EOL, $view->render();
        exit;
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
        $controller = new BlogController($showSearch, $category);
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
     * @param bool $showSearch
     * @return string
     */
    public static function archiveCommand($showSearch = false)
    {
        $controller = new ArchiveController($showSearch);
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
     * @param string $pageUrl
     * @param bool $showTeaser
     * @return string
     */
    public static function linkCommand($pageUrl, $showTeaser = false)
    {
        $controller = new LinkController($pageUrl, $showTeaser);
        return $controller->defaultAction();
    }

    /**
     * @param string $pageUrl
     * @return string
     */
    public static function mostPopularCommand($pageUrl)
    {
        $controller = new MostPopularController($pageUrl);
        return $controller->defaultAction();
    }

    /**
     * @param string $target
     * @return string
     */
    public static function feedLinkCommand($target = '_self')
    {
        $controller = new FeedLinkController();
        return $controller->defaultAction($target);
    }

    /**
     * @return int
     */
    public static function getPage()
    {
        global $edit;

        if (defined('XH_ADM') && XH_ADM && $edit) {
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
