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
     */
    public function blog($showSearch = false, $category = 'all')
    {
        global $plugin_cf;

        $id = $this->getPgParameter('realblog_id');
        $html = '';
        if (!isset($id)) {
            if ($showSearch) {
                $html .= $this->renderSearchForm();
            }
            $order = ($plugin_cf['realblog']['entries_order'] == 'desc')
                ? -1 : 1;
            $limit = $plugin_cf['realblog']['entries_per_page'];
            $page = $this->getPage();
            $search = $this->getPgParameter('realblog_search');
            $articleCount = DB::countArticlesWithStatus(array(1), $category, $search);
            $pageCount = ceil($articleCount / $limit);
            $page = min(max($page, 1), $pageCount);
            $articles = DB::findArticles(1, $limit, ($page-1) * $limit, $order, $category, $search);
            if ($search) {
                $html .= $this->renderSearchResults('blog', $articleCount);
            }
            $html .= $this->renderArticles($articles, $articleCount, $page, $pageCount);
        } else {
            $html .= $this->renderArticle($id);
        }
        return $html;
    }

    private function renderSearchForm()
    {
        global $su, $sn;

        $view = new View('search-form');
        $view->actionUrl = $sn;
        $view->pageUrl = $su;
        return $view->render();
    }

    private function renderArticles(array $articles, $articleCount, $page, $pageCount)
    {
        global $su, $plugin_cf;

        $view = new View('articles');
        $view->articles = $articles;
        $search = $this->getPgParameter('realblog_search');
        $view->pagination = new PaginationView(
            $articleCount,
            $page,
            $pageCount,
            $this->url($su, array('realblog_page' => '%s', 'realblog_search' => $search))
        );
        $view->hasTopPagination = (bool) $plugin_cf['realblog']['pagination_top'];
        $view->hasBottomPagination = (bool) $plugin_cf['realblog']['pagination_bottom'];
        $view->hasMultiColumns = (bool) $plugin_cf['realblog']['teaser_multicolumns'];
        $view->url = function ($article) use ($search) {
            global $su, $_Realblog_controller;

            return $_Realblog_controller->url(
                $su,
                array(
                    'realblog_id' => $article->id,
                    'realblog_page' => $_Realblog_controller->getPage(),
                    'realblog_search' => $search
                )
            );
        };
        $view->hasLinkedHeader = function ($article) {
            return $article->body_length || (defined('XH_ADM') && XH_ADM);
        };
        $view->date = function ($article) {
            global $plugin_tx;

            return date($plugin_tx['realblog']['date_format'], $article->date);
        };
        $view->teaser = function ($article) {
            return new HtmlString(evaluate_scripting($article->teaser));
        };
        $view->hasReadMore = function ($article) {
            global $plugin_cf;

            return $plugin_cf['realblog']['show_read_more_link']
                && $article->body_length;
        };
        $view->isCommentable = function ($article) {
            global $plugin_cf;

            return $plugin_cf['realblog']['comments_plugin']
                && class_exists("{$plugin_cf['realblog']['comments_plugin']}_RealblogBridge")
                && $article->commentable;
        };
        $view->commentCount = function ($article) {
            global $plugin_cf;

            $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
            $commentsId = "comments{$article->id}";
            return call_user_func(array($bridge, 'count'), $commentsId);
        };
        return $view->render();
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
        global $sn, $su, $h, $s, $title, $description, $plugin_cf, $plugin_tx;

        $article = DB::findById($id);
        if (isset($article)) {
            $title .= $h[$s] . " \xE2\x80\x93 " . $article->title;
            $description = $this->getDescription($article);
            $view = new View('article');
            $view->article = $article;
            $view->isAdmin = defined('XH_ADM') && XH_ADM;
            $view->wantsComments = $this->wantsComments();
            if ($article->status === 2) {
                $params = array('realblog_year' => $this->getYear());
                $view->backText = $plugin_tx['realblog']['archiv_back'];
            } else {
                $params = array('realblog_page' => $this->getPage());
                $view->backText = $plugin_tx['realblog']['blog_back'];
            }
            $params['realblog_search'] = $this->getPgParameter('realblog_search');
            $view->backUrl = $this->url($su, $params);
            $view->editUrl = "$sn?&realblog&admin=plugin_main"
                . "&action=modify_realblog&realblog_id={$article->id}";
            if ($this->wantsComments()) {
                $bridge = "{$plugin_cf['realblog']['comments_plugin']}_RealblogBridge";
                $view->editCommentsUrl = call_user_func(array($bridge, 'getEditUrl'), 'realblog' . $article->id);
            }
            $view->date = date($plugin_tx['realblog']['date_format'], $article->date);
            $story = ($article->body != '') ? $article->body : $article->teaser;
            $view->story = new HtmlString(evaluate_scripting($story));
            $view->renderComments = function ($article) {
                global $plugin_cf;

                if ($article->commentable) {
                    $commentId = 'comments' . $article->id;
                    $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
                    return new HtmlString(call_user_func(array($bridge, 'handle'), $commentId));
                }
            };
            return $view->render();
        }
    }

    private function wantsComments()
    {
        global $plugin_cf;

        $pcf = $plugin_cf['realblog'];
        return $pcf['comments_plugin']
            && class_exists($pcf['comments_plugin'] . '_RealblogBridge');
    }

    /**
     * @param bool $showSearch
     * @return string
     */
    public function archive($showSearch = false)
    {
        $id = $this->getPgParameter('realblog_id');
        $html = '';
        if (!isset($id)) {
            if ($showSearch) {
                $html .= $this->renderSearchForm();
            }

            if ($search = $this->getPgParameter('realblog_search')) {
                $articles = DB::findArchivedArticlesContaining($search);
                $articleCount = count($articles);
                $html .= $this->renderSearchResults('archive', $articleCount);
            } else {
                $articleCount = DB::countArticlesWithStatus(array(2));
                $articles = array();
            }

            $html .= $this->renderArchive($articles);
        } else {
            $html .= $this->renderArticle($id);
        }
        return $html;
    }

    private function renderArchive(array $articles)
    {
        if (!$this->getPgParameter('realblog_search')) {
            $year = $this->getYear();
            $years = DB::findArchiveYears();
            $key = array_search($year, $years);
            if ($key === false) {
                $key = count($years) - 1;
                $year = $years[$key];
            }
            $back = ($key > 0) ? $years[$key - 1] : null;
            $next = ($key < count($years) - 1) ? $years[$key + 1] : null;
            $articles = DB::findArchivedArticlesInPeriod(
                mktime(0, 0, 0, 1, 1, $year),
                mktime(0, 0, 0, 1, 1, $year + 1)
            );
            return $this->renderArchivedArticles($articles, false, $back, $next);
        } else {
            return $this->renderArchivedArticles($articles, true, null, null);
        }
    }

    private function renderArchivedArticles(array $articles, $isSearch, $back, $next)
    {
        global $su, $plugin_tx;

        $view = new View('archive');
        $view->isSearch = $isSearch;
        $view->articles = $articles;
        $view->year = $this->getYear();
        if ($back) {
            $view->backUrl = $this->url($su, array('realblog_year' => $back));
        }
        if ($next) {
            $view->nextUrl = $this->url($su, array('realblog_year' => $next));
        }
        $view->url = function (stdClass $article) {
            global $su, $_Realblog_controller;

            return $_Realblog_controller->url(
                $su,
                array(
                    'realblog_id' => $article->id,
                    'realblog_year' => date('Y', $article->date),
                    'realblog_search' => $_Realblog_controller->getPgParameter('realblog_search')
                )
            );
        };
        $view->formatDate = function (stdClass $article) {
            global $plugin_tx;

            return date($plugin_tx['realblog']['date_format'], $article->date);
        };
        $view->yearOf = function (stdClass $article) {
            return date('Y', $article->date);
        };
        $view->monthOf = function (stdClass $article) {
            return date('n', $article->date);
        };
        $view->monthName = function ($month) {
            global $plugin_tx;
    
            $monthNames = explode(',', $plugin_tx['realblog']['date_months']);
            return $monthNames[$month - 1];
        };
        return $view->render();
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
            $html .= '</div>';
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

        $url = $this->url($pageURL, array('realblog_id' => $article->id));
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
        global $edit;

        if (defined('XH_ADM') && XH_ADM && $edit) {
            if (isset($_GET['realblog_page'])) {
                $page = (int) $_GET['realblog_page'];
                $_COOKIE['realblog_page'] = $page;
                setcookie('realblog_page', $page, 0, CMSIMPLE_ROOT);
            } elseif (isset($_COOKIE['realblog_page'])) {
                $page = (int) $_COOKIE['realblog_page'];
            } else {
                $page = 1;
            }
        } else {
            if (isset($_GET['realblog_page'])) {
                $page = $_GET['realblog_page'];
            } else {
                $page = 1;
            }
        }
        return $page;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        if (isset($_GET['realblog_year'])) {
            $year = $_GET['realblog_year'];
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
        if (isset($_GET["realblog_filter$num"])) {
            $filter = ($_GET["realblog_filter$num"] == 'on');
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
