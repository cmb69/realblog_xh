<?php

/**
 * @category  CMSimple_XH
 * @package   Realblog
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

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
            if (isset($_GET['realblog_feed']) && $_GET['realblog_feed'] == 'rss') {
                $this->deliverFeed();
            }
        }
        if (XH_ADM) {
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
        global $realblog;

        return isset($realblog) && $realblog == 'true';
    }

    /**
     * @return void
     */
    private function handleAdministration()
    {
        $controller = new AdminController();
        $controller->dispatch();
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
     * @param bool $showSearch
     * @param string $category
     * @return string
     * @global array $plugin_cf
     * @global Controller $_Realblog_controller
     */
    public function blog($showSearch = false, $category = 'all')
    {
        global $plugin_cf, $_Realblog_controller;

        $id = $this->getPgParameter('realblogID');
        $html = '';
        if (!isset($id)) {
            if ($showSearch) {
                $view = new SearchFormView($this->getYear());
                $html .= $view->render();
            }
            $order = ($plugin_cf['realblog']['entries_order'] == 'desc')
                ? -1 : 1;
            $limit = $plugin_cf['realblog']['entries_per_page'];
            $page = $_Realblog_controller->getPage();
            $search = $this->getPgParameter('realblog_search');
            $articleCount = DB::countArticlesWithStatus(array(1), $category, $search);
            $pageCount = ceil($articleCount / $limit);
            $page = min(max($page, 1), $pageCount);
            $articles = DB::findArticles(1, $limit, ($page-1) * $limit, $order, $category, $search);
            if ($search) {
                $html .= $this->renderSearchResults('blog', $articleCount);
            }
            $view = new ArticlesView($articles, $articleCount, $page, $pageCount);
            $html .= $view->render();
        } else {
            $html .= $this->renderArticle($id);
        }
        return $html;
    }

    /**
     * @param int $id
     * @return string
     * @global array $h
     * @global int $s
     * @global string $title
     * @global string $description
     */
    private function renderArticle($id)
    {
        global $h, $s, $title, $description;

        $article = DB::findById($id);
        if (isset($article)) {
            $title .= $h[$s] . " \xE2\x80\x93 " . $article->title;
            $description = $this->getDescription($article);
            $view = new ArticleView($id, $article, $this->getPage());
            return $view->render();
        }
    }

    /**
     * @param bool $showSearch
     * @return string
     */
    public function archive($showSearch = false)
    {
        $realblogID = $this->getPgParameter('realblogID');
        $html = '';
        if (!isset($realblogID)) {
            if ($showSearch) {
                $view = new SearchFormView($this->getYear());
                $html .= $view->render();
            }

            if ($search = $this->getPgParameter('realblog_search')) {
                $articles = DB::findArchivedArticlesContaining($search);
                $articleCount = count($articles);
                $html .= $this->renderSearchResults('archive', $articleCount);
            } else {
                $articleCount = DB::countArticlesWithStatus(array(2));
                $articles = array();
            }

            $view = new ArchiveView($articles, $articleCount);
            $html .= $view->render();
        } else {
            $html .= $this->renderArticle($realblogID);
        }
        return $html;
    }

    /**
     * @param string $pageUrl
     * @return string
     * @global array $u
     * @global array $plugin_cf
     * @global array $plugin_tx
     */
    public function link($pageUrl)
    {
        global $u, $plugin_cf, $plugin_tx;

        if (!in_array($pageUrl, $u)) {
            return '';
        }
        if ($plugin_cf['realblog']['links_visible'] <= 0) {
            return '';
        }
        $html = '<p class="realbloglink">'
            . $plugin_tx['realblog']['links_visible_text'] . '</p>';
        $articles = DB::findArticles(1, $plugin_cf['realblog']['links_visible']);
        if (!empty($articles)) {
            $html .= '<div class="realblog_tpl_show_box">';
            foreach ($articles as $article) {
                $html .= $this->renderArticleLink($article, $pageUrl);
            }
            $html .= '<div style="clear: both;"></div></div>';
        } else {
            $html .= $plugin_tx['realblog']['no_topics'];
        }
        return $html;
    }

    /**
     * @param string $pageURL
     * @return string
     * @global array $plugin_tx
     */
    private function renderArticleLink(stdClass $article, $pageURL)
    {
        global $plugin_tx;

        $url = $this->url(
            $pageURL,
            $article->title,
            array('realblogID' => $article->id)
        );
        return '<div class="realblog_tpl_show_date">'
            . date($plugin_tx['realblog']['date_format'], $article->date)
            . '</div>'
            . '<div class="realblog_tpl_show_title">'
            . '<a href="' . XH_hsc($url) . '">' . $article->title .'</a>'
            . '</div>';
    }

    /**
     * @return string
     * @global array $pth
     * @global array $plugin_tx
     */
    public function feedLink()
    {
        global $pth, $plugin_tx;

        return '<a href="./?realblog_feed=rss">'
            . tag(
                'img src="' . $pth['folder']['plugins'] . 'realblog/images/rss.png"'
                . ' alt="' . $plugin_tx['realblog']['rss_tooltip'] . '" title="'
                . $plugin_tx['realblog']['rss_tooltip'] . '" style="border: 0;"'
            )
            . '</a>';
    }

    /**
     * @return void
     */
    private function deliverFeed()
    {
        global $plugin_cf;

        header('Content-Type: application/rss+xml; charset=UTF-8');
        $count = $plugin_cf['realblog']['rss_entries'];
        $view = new RSSFeed(DB::findFeedableArticles($count));
        echo $view->render();
        exit();
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
     * @return string
     */
    private function getDescription(stdClass $article)
    {
        return utf8_substr(
            html_entity_decode(strip_tags($article->teaser), ENT_COMPAT, 'UTF-8'),
            0,
            150
        );
    }

    /**
     * @param string $name
     * @return string
     */
    public function getPgParameter($name)
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        } elseif (isset($_GET[$name])) {
            return $_GET[$name];
        } else {
            return null;
        }
    }

    /**
     * @return int
     */
    public function getPage()
    {
        if (isset($_GET['realblog_page'])) {
            $page = (int) $_GET['realblog_page'];
            $_COOKIE['realblog_page'] = $page;
            setcookie('realblog_page', $page, 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE['realblog_page'])) {
            $page = (int) $_COOKIE['realblog_page'];
        } else {
            $page = 1;
        }
        return $page;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        if (isset($_GET['realblog_year'])) {
            $year = (int) $_GET['realblog_year'];
            $_COOKIE['realblog_year'] = $year;
            setcookie('realblog_year', $year, 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE['realblog_year'])) {
            $year = (int) $_COOKIE['realblog_year'];
        } else {
            $year = date('Y');
        }
        return $year;
    }

    /**
     * @param int $num
     * @return bool
     */
    public function getFilter($num)
    {
        if (isset($_POST["realblog_filter$num"])) {
            $filter = ($_POST["realblog_filter$num"] == 'on');
            $_COOKIE["realblog_filter$num"] = $filter ? 'on' : '';
            setcookie("realblog_filter$num", $filter ? 'on' : '', 0, CMSIMPLE_ROOT);
        } elseif (isset($_COOKIE["realblog_filter$num"])) {
            $filter = ($_COOKIE["realblog_filter$num"] == 'on');
        } else {
            $filter = false;
        }
        return $filter;
    }

    /**
     * @param string $pageUrl
     * @param string $articleTitle
     * @param array  $params
     * @return string
     */
    public function url($pageUrl, $articleTitle = null, $params = array())
    {
        global $sn;

        $replacePairs = array(
            //'realblogID' => 'id',
            //'realblog_page' => 'page'
        );
        $url = $sn . '?' . $pageUrl;
        if (isset($articleTitle)) {
            $url .= '&' . uenc($articleTitle);
        }
        ksort($params);
        foreach ($params as $name => $value) {
            $url .= '&' . strtr($name, $replacePairs) . '=' . $value;
        }
        return $url;
    }

    /**
     * @param string $what
     * @param string $count
     * @return string
     * @global string $su
     * @global array $plugin_tx
     */
    private function renderSearchResults($what, $count)
    {
        global $su, $plugin_tx;

        $key = ($what == 'archive') ? 'back_to_archive' : 'search_show_all';
        $search = $this->getPgParameter('realblog_search');
        $words = '"' . $search . '"';
        return '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>'
            . XH_hsc($words) . '</b></p>'
            . '<p>' . $plugin_tx['realblog']['search_result'] . '<b> '
            . $count . '</b></p>'
            . '<p><a href="' . XH_hsc($this->url($su)) . '"><b>'
            . $plugin_tx['realblog'][$key] . '</b></a></p>';
    }

    /**
     * @param string $date
     * @return int
     */
    public function stringToTime($date)
    {
        $parts = explode('-', $date);
        return mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
    }
}
