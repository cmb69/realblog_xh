<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

class BlogController extends MainController
{
    private $category;

    public function __construct($showSearch = false, $category = 'all')
    {
        parent::__construct($showSearch);
        $this->category = $category;
    }

    public function defaultAction()
    {
        $html = '';
        if ($this->showSearch) {
            $html .= $this->renderSearchForm();
        }
        $order = ($this->config['entries_order'] == 'desc')
            ? -1 : 1;
        $limit = max(1, $this->config['entries_per_page']);
        $page = Realblog::getPage();
        $articleCount = DB::countArticlesWithStatus(array(1), $this->category, $this->searchTerm);
        $pageCount = ceil($articleCount / $limit);
        $page = min(max($page, 1), $pageCount);
        $articles = DB::findArticles(1, $limit, ($page-1) * $limit, $order, $this->category, $this->searchTerm);
        if ($this->searchTerm) {
            $html .= $this->renderSearchResults('blog', $articleCount);
        }
        $html .= $this->renderArticles($articles, $articleCount, $page, $pageCount);
        return $html;
    }

    private function renderArticles(array $articles, $articleCount, $page, $pageCount)
    {
        global $su;

        $view = new View('articles');
        $view->articles = $articles;
        $view->heading = $this->config['heading_level'];
        $search = $this->searchTerm;
        $view->pagination = new Pagination(
            $articleCount,
            $page,
            $pageCount,
            Realblog::url($su, array('realblog_page' => '%s', 'realblog_search' => $search))
        );
        $view->hasTopPagination = (bool) $this->config['pagination_top'];
        $view->hasBottomPagination = (bool) $this->config['pagination_bottom'];
        $view->url = function ($article) use ($search) {
            global $su;

            return Realblog::url(
                $su,
                array(
                    'realblog_id' => $article->id,
                    'realblog_page' => Realblog::getPage(),
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

    public function showArticleAction($id)
    {
        return $this->renderArticle($id);
    }
}
