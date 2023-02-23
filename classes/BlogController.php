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
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Infra\Pagination;
use Realblog\Infra\Request;
use Realblog\Infra\Response;
use Realblog\Infra\Url;
use Realblog\Infra\View;
use Realblog\Logic\Util;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class BlogController
{
    /** @var array<string,string> */
    private $conf;

    /** @var DB */
    private $db;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /** @var Pages */
    private $pages;

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        DB $db,
        Finder $finder,
        View $view,
        Pages $pages
    ) {
        $this->conf = $conf;
        $this->db = $db;
        $this->finder = $finder;
        $this->view = $view;
        $this->pages = $pages;
    }

    public function __invoke(Request $request, string $mode, bool $showSearch, string $category = ""): Response
    {
        assert(in_array($mode, ["blog", "archive"], true));
        $this->request = $request;
        $this->response = new Response;
        if ($request->admin() && $request->edit() && $request->hasGet("realblog_page")) {
            $page = max($request->intFromget("realblog_page"), 1);
            $this->response->addCookie('realblog_page', (string) $page);
        }
        if ($request->hasGet("realblog_id")) {
            $this->renderArticle(max($request->intFromGet("realblog_id"), 1));
            return $this->response;
        }
        if ($mode === "blog") {
            return $this->response->setOutput($this->allPosts($showSearch, $category));
        }
        return $this->response->setOutput($this->allArchivedPosts($showSearch));
    }

    private function allPosts(bool $showSearch, string $category): string
    {
        $html = '';
        if ($showSearch) {
            $html .= $this->renderSearchForm($this->request->url());
        }
        $order = ($this->conf['entries_order'] == 'desc')
            ? -1 : 1;
        $limit = max(1, (int) $this->conf['entries_per_page']);
        $page = $this->getPage();
        $searchTerm = $this->request->stringFromGet("realblog_search");
        $articleCount = $this->finder->countArticlesWithStatus(array(1), $category, $searchTerm);
        $pageCount = (int) ceil($articleCount / $limit);
        $page = min(max($page, 1), $pageCount);
        $articles = $this->finder->findArticles(
            1,
            $limit,
            ($page-1) * $limit,
            $order,
            $category,
            $searchTerm
        );
        if ($searchTerm) {
            $html .= $this->renderSearchResults($this->request->url(), 'blog', $articleCount);
        }
        $html .= $this->renderArticles($articles, $articleCount, $page, $pageCount);
        return $html;
    }

    /** @param list<Article> $articles */
    private function renderArticles(
        array $articles,
        int $articleCount,
        int $page,
        int $pageCount
    ): string {
        $searchTerm = $this->request->stringFromGet("realblog_search");
        $bridge = ucfirst($this->conf["comments_plugin"]) . "\\RealblogBridge";
        $params = ["realblog_page" => (string) $this->getPage(), "realblog_search" => $searchTerm];
        $records = [];
        foreach ($articles as $article) {
            $isCommentable = $this->conf["comments_plugin"] && class_exists($bridge) && $article->commentable;
            $records[] = [
                "title" => $article->title,
                "url" => $this->request->url()->withParams(["realblog_id" => (string) $article->id] + $params)->relative(),
                "categories" => implode(", ", explode(",", trim($article->categories, ","))),
                "link_header" => $article->hasBody || $this->request->admin(),
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
            $this->request->url()->withParams(["realblog_search" => $searchTerm]),
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

    private function renderSearchForm(Url $url): string
    {
        return $this->view->render('search-form', [
            'actionUrl' => $url->withPage("")->relative(),
            'pageUrl' => $url->page(),
        ]);
    }

    private function renderSearchResults(Url $url, string $what, int $count): string
    {
        return $this->view->render('search-results', [
            'words' => $this->request->stringFromGet("realblog_search"),
            'count' => $count,
            'url' => $url->relative(),
            'key' => ($what == 'archive') ? 'back_to_archive' : 'search_show_all',
        ]);
    }

    /** @return void */
    private function renderArticle(int $id)
    {
        $article = $this->finder->findById($id);
        if (isset($article) && $this->request->admin() && $article->status > 0) {
            $this->db->recordPageView($id);
        }
        if (isset($article) && ($this->request->admin() || $article->status > 0)) {
            $this->doRenderArticle($article);
        }
    }

    /** @return void */
    private function doRenderArticle(FullArticle $article)
    {
        $teaser = trim(html_entity_decode(strip_tags($article->teaser), ENT_COMPAT, 'UTF-8'));
        $this->response
            ->setTitle($this->pages->headingOf($this->request->page()) . " – " . $article->title)
            ->setDescription(Util::shortenText($teaser));
        if ($article->status === 2) {
            $params = array('realblog_year' => (string) $this->request->year());
        } else {
            $params = array('realblog_page' => (string) $this->getPage());
        }

        $bridge = ucfirst($this->conf['comments_plugin']) . '\\RealblogBridge';

        $data = [
            'title' => $article->title,
            'heading' => $this->conf['heading_level'],
            'heading_above_meta' => $this->conf['heading_above_meta'],
            'is_admin' => $this->request->admin(),
            'wants_comments' => $this->wantsComments(),
            'back_text' => $article->status === 2 ? 'archiv_back' : 'blog_back',
            'back_url' => $this->request->url()->withParams($params)->relative(),
        ];
        $searchTerm = $this->request->stringFromGet("realblog_search");
        if ($searchTerm) {
            $params['realblog_search'] = $searchTerm;
            $data['back_to_search_url'] = $this->request->url()->withParams($params)->relative();
        }
        $data['edit_url'] = $this->request->url()->withPage("realblog")
            ->withParams(["admin" => "plugin_main", "action" => "edit", "realblog_id" => (string) $article->id])
            ->relative();
        if ($this->wantsComments()) {
            /** @var class-string $bridge */
            $commentsUrl = $bridge::getEditUrl("realblog{$article->id}");
            if ($commentsUrl !== false) {
                $data['edit_comments_url'] = $commentsUrl;
            }
            $data['comment_count'] = $bridge::count("realblog{$article->id}");
            $data["comments"] = $bridge::handle("realblog{$article->id}");
        }
        $data['date'] = $this->view->date($article->date);
        $categories = explode(',', trim($article->categories, ','));
        $data['categories'] = implode(', ', $categories);
        if ($this->conf['show_teaser']) {
            $story = '<div class="realblog_teaser">' . $article->teaser . '</div>' . $article->body;
        } else {
            $story = ($article->body != '') ? $article->body : $article->teaser;
        }
        $data['story'] = $this->pages->evaluateScripting($story);
        $this->response->setOutput($this->view->render('article', $data));
    }

    private function wantsComments(): bool
    {
        return $this->conf['comments_plugin']
            && class_exists(ucfirst($this->conf['comments_plugin']) . '\\RealblogBridge');
    }

    private function getPage(): int
    {
        if ($this->request->admin() && $this->request->edit()) {
            return max($this->request->intFromGetOrCookie("realblog_page"), 1);
        }
        return max($this->request->intFromGet("realblog_page"), 1);
    }

    private function allArchivedPosts(bool $showSearch): string
    {
        $html = '';
        if ($showSearch) {
            $html .= $this->renderSearchForm($this->request->url());
        }
        $searchTerm = $this->request->stringFromGet("realblog_search");
        if ($searchTerm) {
            $articles = $this->finder->findArchivedArticlesContaining($searchTerm);
            $articleCount = count($articles);
            $html .= $this->renderSearchResults($this->request->url(), 'archive', $articleCount);
        } else {
            $articles = array();
        }

        $html .= $this->renderArchive($articles);
        return $html;
    }

    /** @param list<Article> $articles */
    private function renderArchive(array $articles): string
    {
        if ($this->request->stringFromGet("realblog_search") === "") {
            $year = $this->request->year();
            $years = $this->finder->findArchiveYears();
            $key = array_search($year, $years);
            if ($key === false) {
                $key = count($years) - 1;
                $year = $years[$key];
            }
            $back = ($key > 0) ? $years[(int) $key - 1] : null;
            $next = ($key < count($years) - 1) ? $years[(int) $key + 1] : null;
            $articles = $this->finder->findArchivedArticlesInPeriod(
                (int) mktime(0, 0, 0, 1, 1, $year),
                (int) mktime(0, 0, 0, 1, 1, $year + 1)
            );
            return $this->renderArchivedArticles($articles, false, $back, $next);
        } else {
            return $this->renderArchivedArticles($articles, true, null, null);
        }
    }

    /** @param list<Article> $articles */
    private function renderArchivedArticles(
        array $articles,
        bool $isSearch,
        ?int $back,
        ?int $next
    ): string {
        $records = [];
        foreach (Util::groupArticlesByMonth($articles) as $group) {
            $groupRecords = [];
            foreach ($group as $article) {
                $params = [
                    'realblog_id' => (string) $article->id,
                    'realblog_year' => date('Y', $article->date),
                    'realblog_search' => $this->request->stringFromGet("realblog_search"),
                ];
                $groupRecords[] = [
                    "title" => $article->title,
                    "date" => $this->view->date($article->date),
                    "url" => $this->request->url()->withParams($params)->relative(),
                    "year" => idate('Y', $article->date),
                    "month" => idate('n', $article->date) - 1,
                ];
            }
            $records[] = $groupRecords;
        }

        $data = [
            'isSearch' => $isSearch,
            'articles' => $records,
            'heading' => $this->conf['heading_level'],
            'year' => $this->request->year(),
        ];
        if ($back) {
            $data['backUrl'] = $this->request->url()->withParams(['realblog_year' => (string) $back])->relative();
        }
        if ($next) {
            $data['nextUrl'] = $this->request->url()->withParams(['realblog_year' => (string) $next])->relative();
        }
        return $this->view->render('archive', $data);
    }
}
