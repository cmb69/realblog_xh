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
use Realblog\Infra\Request;
use Realblog\Infra\Url;
use Realblog\Infra\View;
use Realblog\Logic\Util;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use Realblog\Value\Response;

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
        $response = $this->dispatch($request, $mode, $showSearch, $category);
        if ($request->admin() && $request->edit() && $request->hasGet("realblog_page")) {
            $page = max($request->intFromget("realblog_page"), 1);
            $response = $response->withCookie("realblog_page", (string) $page);
        }
        return $response;
    }

    private function dispatch(Request $request, string $mode, bool $showSearch, string $category): Response
    {
        if ($request->hasGet("realblog_id")) {
            return $this->oneArticle(max($request->intFromGet("realblog_id"), 1));
        }
        if ($mode === "blog") {
            return $this->allArticles($showSearch, $category);
        }
        return $this->allArchivedArticles($showSearch);
    }

    private function allArticles(bool $showSearch, string $category): Response
    {
        $html = "";
        if ($showSearch) {
            $html .= $this->renderSearchForm($this->request->url());
        }
        $order = ($this->conf["entries_order"] == "desc") ? -1 : 1;
        $limit = max(1, (int) $this->conf["entries_per_page"]);
        $page = $this->request->realblogPage();
        $searchTerm = $this->request->stringFromGet("realblog_search");
        $articleCount = $this->finder->countArticlesWithStatus([Article::PUBLISHED], $category, $searchTerm);
        $pageCount = (int) ceil($articleCount / $limit);
        $page = min(max($page, 1), $pageCount);
        $articles = $this->finder->findArticles(1, $limit, ($page-1) * $limit, $order, $category, $searchTerm);
        if ($searchTerm) {
            $html .= $this->renderSearchResults($this->request->url(), "blog", $articleCount);
        }
        $html .= $this->renderArticles($articles, $articleCount, $page, $pageCount);
        return Response::create($html);
    }

    /** @param list<Article> $articles */
    private function renderArticles(array $articles, int $articleCount, int $page, int $pageCount): string
    {
        $searchTerm = $this->request->stringFromGet("realblog_search");
        $radius = (int) $this->conf["pagination_radius"];
        $url = $this->request->url()->withParams(["realblog_search" => $searchTerm]);
        $params = ["realblog_page" => (string) $this->request->realblogPage(), "realblog_search" => $searchTerm];
        return $this->view->render("articles", [
            "articles" => $this->articleRecords($articles, $params),
            "heading" => $this->conf["heading_level"],
            "heading_above_meta" => $this->conf["heading_above_meta"],
            "pagination" => $this->renderPagination($articleCount, $page, $pageCount, $radius, $url),
            "top_pagination" => (bool) $this->conf["pagination_top"],
            "bottom_pagination" => (bool) $this->conf["pagination_bottom"],
        ]);
    }

    /**
     * @param list<Article> $articles
     * @param array{realblog_page:string,realblog_search:string} $params
     * @return list<array{title:string,url:string,categories:string,link_header:bool,date:string,teaser:html,read_more:bool,commentable:bool,comment_count:int}>
     */
    private function articleRecords(array $articles, array $params): array
    {
        $bridge = ucfirst($this->conf["comments_plugin"]) . "\\RealblogBridge";
        $records = [];
        foreach ($articles as $article) {
            $isCommentable = $this->conf["comments_plugin"] && class_exists($bridge) && $article->commentable;
            $records[] = [
                "title" => $article->title,
                "url" => $this->request->url()
                    ->withParams(["realblog_id" => (string) $article->id] + $params)->relative(),
                "categories" => implode(", ", explode(",", trim($article->categories, ","))),
                "link_header" => $article->hasBody || $this->request->admin(),
                "date" => $this->view->date($article->date),
                "teaser" => $this->pages->evaluateScripting($article->teaser),
                "read_more" => $this->conf["show_read_more_link"]  && $article->hasBody,
                "commentable" => $isCommentable,
                "comment_count" => $isCommentable ? $bridge::count("realblog{$article->id}") : null,
            ];
        }
        return $records;
    }

    /** @return string */
    private function renderPagination(int $itemCount, int $page, int $pageCount, int $radius, Url $url)
    {
        if ($pageCount <= 1) {
            return "";
        }
        $pages = [];
        foreach (Util::gatherPages($page, $pageCount, $radius) as $page) {
            $pages[] = $page !== null ? ["num" => $page, "url" => $url->withRealblogPage($page)->relative()] : null;
        }
        return $this->view->render("pagination", [
            "itemCount" => $itemCount,
            "currentPage" => $page,
            "pages" => $pages,
        ]);
    }

    private function oneArticle(int $id): Response
    {
        $article = $this->finder->findById($id);
        if (isset($article)) {
            if (!$this->request->admin() && $article->status > Article::UNPUBLISHED) {
                $this->db->recordPageView($id);
            }
            if ($this->request->admin() || $article->status > Article::UNPUBLISHED) {
                return $this->renderArticle($article);
            }
        }
        return Response::create();
    }

    private function renderArticle(FullArticle $article): Response
    {
        $teaser = trim(html_entity_decode(strip_tags($article->teaser), ENT_COMPAT, "UTF-8"));
        if ($article->status === Article::ARCHIVED) {
            $params = ["realblog_year" => (string) $this->request->year()];
        } else {
            $params = ["realblog_page" => (string) $this->request->realblogPage()];
        }
        $bridge = ucfirst($this->conf["comments_plugin"]) . "\\RealblogBridge";
        $backUrl = $this->request->url()->withParams($params)->relative();
        $searchTerm = $this->request->stringFromGet("realblog_search");
        if ($searchTerm !== "") {
            $params["realblog_search"] = $searchTerm;
            $backToSearchUrl = $this->request->url()->withParams($params)->relative();
        }
        $editUrl = $this->request->url()->withPage("realblog")
            ->withParams(["admin" => "plugin_main", "action" => "edit", "realblog_id" => (string) $article->id])
            ->relative();
        if ($this->conf["comments_plugin"] && class_exists($bridge)) {
            $commentsUrl = $bridge::getEditUrl("realblog{$article->id}");
        }
        if ($this->conf["show_teaser"]) {
            $story = "<div class=\"realblog_teaser\">" . $article->teaser . "</div>" . $article->body;
        } else {
            $story = ($article->body !== "") ? $article->body : $article->teaser;
        }
        return Response::create($this->view->render("article", [
            "title" => $article->title,
            "heading" => $this->conf["heading_level"],
            "heading_above_meta" => $this->conf["heading_above_meta"],
            "is_admin" => $this->request->admin(),
            "wants_comments" => $this->conf["comments_plugin"] && class_exists($bridge),
            "back_text" => $article->status === 2 ? "archiv_back" : "blog_back",
            "back_url" => $backUrl,
            "back_to_search_url" => $backToSearchUrl ?? null,
            "edit_url" => $editUrl,
            "edit_comments_url" => !empty($commentsUrl) ? $commentsUrl : null,
            "comment_count" => !empty($commentsUrl) ? $bridge::count("realblog{$article->id}") : null,
            "comments" => !empty($commentsUrl) ? $bridge::handle("realblog{$article->id}"): null,
            "date" => $this->view->date($article->date),
            "categories" => implode(", ", explode(",", trim($article->categories, ","))),
            "story" => $this->pages->evaluateScripting($story),
        ]))->withTitle($this->pages->headingOf($this->request->page()) . " – " . $article->title)
            ->withDescription(Util::shortenText($teaser));
    }

    private function allArchivedArticles(bool $showSearch): Response
    {
        $html = "";
        if ($showSearch) {
            $html .= $this->renderSearchForm($this->request->url());
        }
        $searchTerm = $this->request->stringFromGet("realblog_search");
        if ($searchTerm) {
            $articles = $this->finder->findArchivedArticlesContaining($searchTerm);
            $html .= $this->renderSearchResults($this->request->url(), "archive", count($articles));
        } else {
            $articles = array();
        }
        $html .= $this->renderArchive($articles);
        return Response::create($html);
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
        }
        return $this->renderArchivedArticles($articles, true, null, null);
    }

    /** @param list<Article> $articles */
    private function renderArchivedArticles(array $articles, bool $isSearch, ?int $back, ?int $next): string
    {
        $url = $this->request->url();
        return $this->view->render("archive", [
            "isSearch" => $isSearch,
            "articles" => $this->archivedArticleRecords($articles),
            "heading" => $this->conf["heading_level"],
            "year" => $this->request->year(),
            "backUrl" => $back ? $url->withParams(["realblog_year" => (string) $back])->relative() : null,
            "nextUrl" => $next ? $url->withParams(["realblog_year" => (string) $next])->relative() : null,
        ]);
    }

    /**
     * @param list<Article> $articles
     * @return list<list<array{title:string,date:string,url:string,year:string,month:int}>>
     */
    private function archivedArticleRecords($articles): array
    {
        $records = [];
        foreach (Util::groupArticlesByMonth($articles) as $group) {
            $groupRecords = [];
            foreach ($group as $article) {
                $params = [
                    "realblog_id" => (string) $article->id,
                    "realblog_year" => date("Y", $article->date),
                    "realblog_search" => $this->request->stringFromGet("realblog_search"),
                ];
                $groupRecords[] = [
                    "title" => $article->title,
                    "date" => $this->view->date($article->date),
                    "url" => $this->request->url()->withParams($params)->relative(),
                    "year" => date("Y", $article->date),
                    "month" => idate("n", $article->date) - 1,
                ];
            }
            $records[] = $groupRecords;
        }
        return $records;
    }

    private function renderSearchForm(Url $url): string
    {
        return $this->view->render("search_form", [
            "actionUrl" => $url->withPage("")->relative(),
            "pageUrl" => $url->page(),
        ]);
    }

    private function renderSearchResults(Url $url, string $what, int $count): string
    {
        return $this->view->render("search_results", [
            "words" => $this->request->stringFromGet("realblog_search"),
            "count" => $count,
            "url" => $url->relative(),
            "key" => ($what == "archive") ? "back_to_archive" : "search_show_all",
        ]);
    }
}
