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

use Plib\Request;
use Plib\Response;
use Plib\Url;
use Plib\View;
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Logic\Util;
use Realblog\Value\Article;

class BlogController
{
    /** @var array<string,string> */
    private $conf;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /** @var Pages */
    private $pages;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        Finder $finder,
        View $view,
        Pages $pages
    ) {
        $this->conf = $conf;
        $this->finder = $finder;
        $this->view = $view;
        $this->pages = $pages;
    }

    public function __invoke(Request $request, string $mode, bool $showSearch, string $category = ""): Response
    {
        assert(in_array($mode, ["blog", "archive"], true));
        if ($mode === "blog") {
            return $this->allArticles($request, $showSearch, $category);
        }
        return $this->allArchivedArticles($request, $showSearch);
    }

    private function allArticles(Request $request, bool $showSearch, string $category): Response
    {
        $html = "";
        if ($showSearch) {
            $html .= $this->renderSearchForm($request, "blog");
        }
        $order = ($this->conf["entries_order"] == "desc") ? -1 : 1;
        $limit = max(1, (int) $this->conf["entries_per_page"]);
        $searchTerm = $request->get("realblog_search") ?? "";
        $articleCount = $this->finder->countArticlesWithStatus(Article::MASK_PUBLISHED, $category, $searchTerm);
        [$offset, $pageCount, $page] = Util::paginationOffset($articleCount, $limit, $this->realblogPage($request));
        $articles = $this->finder->findArticles(Article::PUBLISHED, $limit, $offset, $order, $category, $searchTerm);
        if ($searchTerm) {
            $html .= $this->renderSearchResults($request, "blog", $articleCount);
        }
        $html .= $this->renderArticles($request, $articles, $articleCount, $page, $pageCount);
        return Response::create($html);
    }

    /** @param list<Article> $articles */
    private function renderArticles(
        Request $request,
        array $articles,
        int $articleCount,
        int $page,
        int $pageCount
    ): string {
        $searchTerm = $request->get("realblog_search") ?? "";
        $radius = (int) $this->conf["pagination_radius"];
        $url = $request->url()->with("realblog_search", $searchTerm);
        $pagination = $this->renderPagination($articleCount, $page, $pageCount, $radius, $url);
        $url = $request->url()->with("realblog_page", (string) $this->realblogPage($request))
            ->with("realblog_search", $searchTerm);
        return $this->view->render("articles", [
            "articles" => $this->articleRecords($request, $articles, $url),
            "heading" => $this->conf["heading_level"],
            "heading_above_meta" => $this->conf["heading_above_meta"],
            "pagination" => $pagination,
            "top_pagination" => (bool) $this->conf["pagination_top"],
            "bottom_pagination" => (bool) $this->conf["pagination_bottom"],
        ]);
    }

    /**
     * @param list<Article> $articles
     * @return list<array{title:string,url:string,categories:string,link_header:bool,date:string,teaser:string,read_more:bool,commentable:bool,comment_count:int}>
     */
    private function articleRecords(Request $request, array $articles, Url $url): array
    {
        $bridge = ucfirst($this->conf["comments_plugin"]) . "\\RealblogBridge";
        $records = [];
        $articleUrl = $this->articleUrl($request);
        foreach ($articles as $article) {
            $isCommentable = $this->conf["comments_plugin"] && class_exists($bridge) && $article->commentable;
            $records[] = [
                "title" => $article->title,
                "url" => $articleUrl->with("realblog_id", (string) $article->id)->relative(),
                "categories" => implode(", ", explode(",", trim($article->categories, ","))),
                "link_header" => $article->hasBody || $request->admin(),
                "date" => date($this->view->text("date_format"), $article->date),
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
        return $this->view->render("pagination", [
            "itemCount" => $itemCount,
            "pages" => $this->pageRecords($page, $pageCount, $radius, $url),
        ]);
    }

    /**
     * @param int<2,max> $pageCount
     * @return list<array{num:int,url:?string}|null>
     */
    private function pageRecords(int $currentPage, int $pageCount, int $radius, Url $url): array
    {
        $pages = [];
        foreach (Util::gatherPages($currentPage, $pageCount, $radius) as $page) {
            if ($page !== null) {
                $pages[] = [
                    "num" => $page,
                    "url" => $page !== $currentPage ? $url->with("realblog_page", (string) $page)->relative() : null,
                ];
            } else {
                $pages[] = null;
            }
        }
        return $pages;
    }

    private function allArchivedArticles(Request $request, bool $showSearch): Response
    {
        $html = "";
        if ($showSearch) {
            $html .= $this->renderSearchForm($request, "archive");
        }
        $searchTerm = $request->get("realblog_search") ?? "";
        if ($searchTerm) {
            $articles = $this->finder->findArchivedArticlesContaining($searchTerm);
            $html .= $this->renderSearchResults($request, "archive", count($articles));
        } else {
            $year = $this->year($request);
            if ($request->get("realblog_year") === null && $year !== "") {
                $url = $request->url()->without("realblog_search")->with("realblog_year", $year)->absolute();
                return Response::redirect($url);
            }
            $articles = [];
        }
        $html .= $this->renderArchive($request, $articles);
        return Response::create($html);
    }

    /** @param list<Article> $articles */
    private function renderArchive(Request $request, array $articles): string
    {
        if (($request->get("realblog_search") ?? "") === "") {
            $year = (int) $this->year($request);
            $articles = $this->finder->findArchivedArticlesInPeriod(
                (int) mktime(0, 0, 0, 1, 1, $year),
                (int) mktime(0, 0, 0, 1, 1, $year + 1)
            );
            return $this->renderArchivedArticles($request, $articles, false);
        }
        return $this->renderArchivedArticles($request, $articles, true);
    }

    /** @param list<Article> $articles */
    private function renderArchivedArticles(Request $request, array $articles, bool $isSearch): string
    {
        $heading = $this->conf["heading_level"];
        return $this->view->render("archive", [
            "isSearch" => $isSearch,
            "articles" => $this->archivedArticleRecords($request, $articles),
            "heading" => $heading,
            "years" => $isSearch
                ? null
                : $this->yearPaginationRecords($request, $this->finder->findArchiveYears()),
        ]);
    }

    /**
     * @param list<Article> $articles
     * @return list<array{year:int,month:string,articles:list<array{title:string,date:string,url:string}>}>
     */
    private function archivedArticleRecords(Request $request, $articles): array
    {
        $records = [];
        $articleUrl = $this->articleUrl($request);
        foreach (Util::groupArticlesByMonth($articles) as $group) {
            $articleRecords = [];
            foreach ($group["articles"] as $article) {
                $articleRecords[] = [
                    "title" => $article->title,
                    "date" => date($this->view->text("date_format"), $article->date),
                    "url" => $articleUrl->with("realblog_id", (string) $article->id)->relative(),
                ];
            }
            $records[] = [
                "year" => $group["year"],
                "month" => explode(',', $this->view->text("date_months"))[$group["month"] - 1],
                "articles" => $articleRecords
            ];
        }
        return $records;
    }

    /**
     * @param list<int> $years
     * @return list<array{year:int,url:?string}>
     */
    private function yearPaginationRecords(Request $request, array $years)
    {
        $records = [];
        foreach ($years as $year) {
            $records[] = [
                "year" => $year,
                "url" => $year === (int) $this->year($request)
                    ? null
                    : $request->url()->with("realblog_year", (string) $year)->relative(),
            ];
        }
        return $records;
    }

    private function renderSearchForm(Request $request, string $mode): string
    {
        $page = $this->realblogPage($request);
        $year = (int) $this->year($request);
        return $this->view->render("search_form", [
            "selected" => $request->selected(),
            "page" => $mode === "blog" && $page !== 1 ? $page : null,
            "year" => $mode === "archive" && $year ? $year : null,
        ]);
    }

    private function renderSearchResults(Request $request, string $what, int $count): string
    {
        return $this->view->render("search_results", [
            "words" => $request->get("realblog_search") ?? "",
            "count" => $count,
            "url" => $request->url()->without("realblog_search")->relative(),
            "key" => ($what == "archive") ? "back_to_archive" : "search_show_all",
        ]);
    }

    private function articleUrl(Request $request): Url
    {
        $url = $request->url()->page("")->with("function", "realblog_article")
            ->with("realblog_selected", $request->selected());
        if ($request->get("realblog_page") !== null) {
            $url = $url->with("realblog_page", $request->get("realblog_page"));
        } elseif ($request->get("realblog_year") !== null) {
            $url = $url->with("realblog_year", $request->get("realblog_year"));
        }
        if ($request->get("realblog_search") !== null) {
            $url = $url->with("realblog_search", $request->get("realblog_search"));
        }
        return $url;
    }

    private function realblogPage(Request $request): int
    {
        return max(1, (int) $request->get("realblog_page"));
    }

    private function year(Request $request): string
    {
        $param = $request->get("realblog_year");
        if (is_string($param)) {
            return $param;
        }
        $archiveYears = $this->finder->findArchiveYears();
        if (!$archiveYears) {
            return "";
        }
        return (string) end($archiveYears);
    }
}
