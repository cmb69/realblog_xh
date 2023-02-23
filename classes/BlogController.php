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
use Realblog\Infra\Request;
use Realblog\Infra\Response;
use Realblog\Infra\Url;
use Realblog\Value\Article;

class BlogController extends MainController
{
    public function __invoke(Request $request, bool $showSeach, string $category): Response
    {
        if (isset($_GET["realblog_id"])) {
            return $this->showArticleAction($request, max((int) ($_GET["realblog_id"] ?? 1), 1));
        } else {
            return (new Response)->withOutput($this->defaultAction($request, $showSeach, $category));
        }
    }

    private function defaultAction(Request $request, bool $showSearch, string $category): string
    {
        $html = '';
        if ($showSearch) {
            $html .= $this->renderSearchForm($request->url());
        }
        $order = ($this->conf['entries_order'] == 'desc')
            ? -1 : 1;
        $limit = max(1, (int) $this->conf['entries_per_page']);
        $page = $this->getPage($request);
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
            $html .= $this->renderSearchResults($request->url(), 'blog', $articleCount);
        }
        $html .= $this->renderArticles($request, $articles, $articleCount, $page, $pageCount);
        return $html;
    }

    /** @param list<Article> $articles */
    private function renderArticles(Request $request, array $articles, int $articleCount, int $page, int $pageCount): string
    {
        $search = $this->searchTerm;
        $bridge = ucfirst($this->conf["comments_plugin"]) . "\\RealblogBridge";
        $params = ["realblog_page" => (string) $this->getPage($request), "realblog_search" => $search];
        $records = [];
        foreach ($articles as $article) {
            $isCommentable = $this->conf["comments_plugin"] && class_exists($bridge) && $article->commentable;
            $records[] = [
                "title" => $article->title,
                "url" => $request->url()->withParams(["realblog_id" => (string) $article->id] + $params)->relative(),
                "categories" => implode(", ", explode(",", trim($article->categories, ","))),
                "link_header" => $article->hasBody || $request->admin(),
                "date" => $this->view->date($article->date),
                "teaser" => $this->pages->evaluateScripting($article->teaser),
                "read_more" => $this->conf["show_read_more_link"]  && $article->hasBody,
                "commentable" => $isCommentable,
                "comment_count" => $isCommentable ? $bridge::count("realblog{$article->id}") : null,
            ];
        }
        $pagination = new Pagination(
            $articleCount,
            $page,
            $pageCount,
            (int) $this->conf['pagination_radius'],
            $request->url()->withParams(["realblog_search" => $search]),
            $this->view
        );
        return $this->view->render("articles", [
            "articles" => $records,
            "heading" => $this->conf["heading_level"],
            "heading_above_meta" => $this->conf["heading_above_meta"],
            "pagination" => $pagination->render(),
            "top_pagination" => (bool) $this->conf["pagination_top"],
            "bottom_pagination" => (bool) $this->conf["pagination_bottom"],
        ]);
    }

    private function showArticleAction(Request $request, int $id): Response
    {
        return $this->renderArticle($request, $id);
    }
}
