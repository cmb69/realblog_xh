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
    /** @var string */
    private $category;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $text
     * @param bool $showSearch
     * @param string $category
     */
    public function __construct(array $config, array $text, $showSearch, Finder $finder, View $view, $category = 'all')
    {
        parent::__construct($config, $text, $showSearch, $finder, $view);
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        $html = '';
        if ($this->showSearch) {
            $html .= $this->renderSearchForm();
        }
        $order = ($this->config['entries_order'] == 'desc')
            ? -1 : 1;
        $limit = max(1, (int) $this->config['entries_per_page']);
        $page = Plugin::getPage();
        $articleCount = $this->finder->countArticlesWithStatus(array(1), $this->category, $this->searchTerm);
        $pageCount = (int) ceil($articleCount / $limit);
        $page = min(max($page, 1), $pageCount);
        $articles = $this->finder->findArticles(
            1,
            $limit,
            ($page-1) * $limit,
            $order,
            $this->category,
            $this->searchTerm
        );
        if ($this->searchTerm) {
            $html .= $this->renderSearchResults('blog', $articleCount);
        }
        $html .= $this->renderArticles($articles, $articleCount, $page, $pageCount);
        return $html;
    }

    /**
     * @param int $articleCount
     * @param int $page
     * @param int $pageCount
     * @return string
     */
    private function renderArticles(array $articles, $articleCount, $page, $pageCount)
    {
        global $su;

        $search = $this->searchTerm;
        $data = [
            'articles' => $articles,
            'heading' => $this->config['heading_level'],
            'isHeadingAboveMeta' => $this->config['heading_above_meta'],
            'pagination' => new Pagination(
                $articleCount,
                $page,
                $pageCount,
                Plugin::url($su, array('realblog_page' => '%s', 'realblog_search' => $search)),
                $this->view
            ),
            'hasTopPagination' => (bool) $this->config['pagination_top'],
            'hasBottomPagination' => (bool) $this->config['pagination_bottom'],
            'url' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) use ($search) {
                global $su;

                return Plugin::url(
                    $su,
                    array(
                        'realblog_id' => $article->id,
                        'realblog_page' => Plugin::getPage(),
                        'realblog_search' => $search
                    )
                );
            },
            'categories' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) {
                $categories = explode(',', trim($article->categories, ','));
                return implode(', ', $categories);
            },
            'hasLinkedHeader' =>
            /**
             * @param stdClass $article
             * @return bool
             */
            function ($article) {
                /** @psalm-suppress UndefinedConstant */
                return $article->body_length || XH_ADM;
            },
            'date' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) {
                return (string) date($this->text['date_format'], $article->date);
            },
            'teaser' =>
            /**
             * @param stdClass $article
             * @return HtmlString
             */
            function ($article) {
                return new HtmlString(evaluate_scripting($article->teaser));
            },
            'hasReadMore' =>
            /**
             * @param stdClass $article
             * @return bool
             */
            function ($article) {
                return $this->config['show_read_more_link']
                    && $article->body_length;
            },
            'isCommentable' =>
            /**
             * @param stdClass $article
             * @return bool
             */
            function ($article) {
                return $this->config['comments_plugin']
                    && class_exists(ucfirst($this->config['comments_plugin']) . '\\RealblogBridge')
                    && $article->commentable;
            },
            'commentCount' =>
            /**
             * @param stdClass $article
             * @return int
             */
            function ($article) {
                $bridge = ucfirst($this->config['comments_plugin']) . '\\RealblogBridge';
                $commentsId = "realblog{$article->id}";
                return call_user_func(array($bridge, 'count'), $commentsId);
            },
        ];
        return $this->view->render('articles', $data);
    }

    /**
     * @param int $id
     * @return string|null
     */
    public function showArticleAction($id)
    {
        return $this->renderArticle($id);
    }
}
