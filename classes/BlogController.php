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
        $articleCount = Finder::countArticlesWithStatus(array(1), $this->category, $this->searchTerm);
        $pageCount = ceil($articleCount / $limit);
        $page = min(max($page, 1), $pageCount);
        $articles = Finder::findArticles(1, $limit, ($page-1) * $limit, $order, $this->category, $this->searchTerm);
        if ($this->searchTerm) {
            $html .= $this->renderSearchResults('blog', $articleCount);
        }
        $html .= $this->renderArticles($articles, $articleCount, $page, $pageCount);
        return $html;
    }

    private function renderArticles(array $articles, $articleCount, $page, $pageCount)
    {
        global $su, $plugin_cf;

        $view = new View('articles');
        $view->articles = $articles;
        $view->heading = $this->config['heading_level'];
        $view->isHeadingAboveMeta = $plugin_cf['realblog']['heading_above_meta'];
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
        $view->categories = function ($article) {
            $categories = explode(',', trim($article->categories, ','));
            return implode(', ', $categories);
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
                && class_exists(ucfirst($plugin_cf['realblog']['comments_plugin']) . '\\RealblogBridge')
                && $article->commentable;
        };
        $view->commentCount = function ($article) {
            global $plugin_cf;

            $bridge = ucfirst($plugin_cf['realblog']['comments_plugin']) . '\\RealblogBridge';
            $commentsId = "realblog{$article->id}";
            return call_user_func(array($bridge, 'count'), $commentsId);
        };
        return $view->render();
    }

    public function showArticleAction($id)
    {
        return $this->renderArticle($id);
    }
}
