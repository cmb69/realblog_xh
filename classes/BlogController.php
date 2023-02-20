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

use Realblog\Infra\Pagination;
use Realblog\Value\Article;
use Realblog\Value\HtmlString;

class BlogController extends MainController
{
    public function __invoke(bool $showSeach, string $category): string
    {
        if (isset($_GET["realblog_id"])) {
            return (string) $this->showArticleAction(filter_var(
                $_GET["realblog_id"],
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1))
            ));
        } else {
            return $this->defaultAction($showSeach, $category);
        }
    }

    /**
     * @return string
     */
    private function defaultAction(bool $showSearch, string $category)
    {
        $html = '';
        if ($showSearch) {
            $html .= $this->renderSearchForm();
        }
        $order = ($this->config['entries_order'] == 'desc')
            ? -1 : 1;
        $limit = max(1, (int) $this->config['entries_per_page']);
        $page = Plugin::getPage();
        $articleCount = $this->finder->countArticlesWithStatus(array(1), $category, $this->searchTerm);
        $pageCount = (int) ceil($articleCount / $limit);
        $page = min(max($page, 1), $pageCount);
        $articles = $this->finder->findArticles(
            1,
            $limit,
            ($page-1) * $limit,
            $order,
            $category,
            $this->searchTerm
        );
        if ($this->searchTerm) {
            $html .= $this->renderSearchResults('blog', $articleCount);
        }
        $html .= $this->renderArticles($articles, $articleCount, $page, $pageCount);
        return $html;
    }

    /**
     * @param list<Article> $articles
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
            'url' => /** @return string */ function (Article $article) use ($search) {
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
            'categories' => /** @return string */ function (Article $article) {
                $categories = explode(',', trim($article->categories, ','));
                return implode(', ', $categories);
            },
            'hasLinkedHeader' => /** @return bool */ function (Article $article) {
                return $article->hasBody || (defined("XH_ADM") && XH_ADM);
            },
            'date' => /** @return string */ function (Article $article) {
                return (string) date($this->text['date_format'], $article->date);
            },
            'teaser' => /** @return HtmlString */ function (Article $article) {
                return new HtmlString($this->scriptEvaluator->evaluate($article->teaser));
            },
            'hasReadMore' => /** @return bool */ function (Article $article) {
                return $this->config['show_read_more_link']
                    && $article->hasBody;
            },
            'isCommentable' => /** @return bool */ function (Article $article) {
                return $this->config['comments_plugin']
                    && class_exists(ucfirst($this->config['comments_plugin']) . '\\RealblogBridge')
                    && $article->commentable;
            },
            'commentCount' => /** @return int */ function (Article $article) {
                /** @var class-string $bridge */
                $bridge = ucfirst($this->config['comments_plugin']) . '\\RealblogBridge';
                $commentsId = "realblog{$article->id}";
                return $bridge::count($commentsId);
            },
        ];
        return $this->view->render('articles', $data);
    }

    /**
     * @param int $id
     * @return string
     */
    private function showArticleAction($id)
    {
        return $this->renderArticle($id);
    }
}
