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

class Controller
{
    /**
     * @return void
     * @global array $plugin_cf
     */
    public function init()
    {
        global $plugin_cf;

        if ($plugin_cf['realblog']['auto_publish']) {
            $this->autoPublish();
        }
        if ($plugin_cf['realblog']['auto_archive']) {
            $this->autoArchive();
        }
        if ($plugin_cf['realblog']['rss_enabled']) {
            $this->emitAlternateRSSLink();
            $rssFeedRequested = filter_input(
                INPUT_GET,
                'realblog_feed',
                FILTER_VALIDATE_REGEXP,
                array('options' => array('regexp' => '/^rss$/'))
            );
            if ($rssFeedRequested) {
                $this->deliverFeed();
            }
        }
        if (defined('XH_ADM') && XH_ADM) {
            if (function_exists('XH_registerStandardPluginMenuItems')) {
                XH_registerStandardPluginMenuItems(true);
            }
            if ($this->isAdministrationRequested()) {
                $this->handleAdministration();
            }
        }
    }

    /**
     * @return bool
     * @global string $realblog
     */
    private function isAdministrationRequested()
    {
        global $realblog, $su;

        return isset($realblog) && $realblog == 'true' || $su === 'realblog';
    }

    /**
     * @return void
     */
    private function handleAdministration()
    {
        global $admin, $action, $o;

        $o .= print_plugin_admin('on');
        switch ($admin) {
            case '':
                $o .= $this->renderInfoView();
                break;
            case 'plugin_main':
                $this->handleMainAdministration();
                break;
            default:
                $o .= plugin_admin_common($action, $admin, 'realblog');
        }
    }

    private function renderInfoView()
    {
        global $pth;

        $view = new View('info');
        $view->logoPath = "{$pth['folder']['plugins']}realblog/realblog.png";
        $view->version = REALBLOG_VERSION;
        $systemCheck = new SystemCheck();
        return $view->render() . $systemCheck->render();
    }

    private function handleMainAdministration()
    {
        global $o, $action;

        $methodName = lcfirst(implode('', array_map('ucfirst', explode('_', $action)))) . 'Action';
        $controller = new MainAdminController();
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
    private function emitAlternateRSSLink()
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
    private function deliverFeed()
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
        $view->articles = DB::findFeedableArticles($count);
        $view->articleUrl = function ($article) use ($sn, $plugin_tx) {
            global $_Realblog_controller;

            return CMSIMPLE_URL . substr(
                $_Realblog_controller->url(
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
    private function autoPublish()
    {
        DB::autoChangeStatus('publishing_date', 1);
    }

    /**
     * @return void
     */
    private function autoArchive()
    {
        DB::autoChangeStatus('archiving_date', 2);
    }

    /**
     * @return int
     */
    public function getPage()
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
    public function getFilter($num)
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
    public function url($pageUrl, $params = array())
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
