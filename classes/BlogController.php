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

    /** @var string */
    private $searchTerm;

    /** @var int */
    private $year;

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
        $this->searchTerm = $_GET['realblog_search'] ?? "";
        $this->year = (int) ($_GET['realblog_year'] ?? idate("Y"));
    }

    public function __invoke(Request $request, string $mode, bool $showSearch, string $category = ""): Response
    {
        assert(in_array($mode, ["blog", "archive"], true));
        if (isset($_GET["realblog_id"])) {
            return $this->renderArticle($request, max((int) ($_GET["realblog_id"] ?? 1), 1));
        }
        if ($mode === "blog") {
            return (new Response)->withOutput($this->allPosts($request, $showSearch, $category));
        }
        return (new Response)->withOutput($this->allArchivedPosts($request, $showSearch));
    }

    private function allPosts(Request $request, bool $showSearch, string $category): string
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
    private function renderArticles(
        Request $request,
        array $articles,
        int $articleCount,
        int $page,
        int $pageCount
    ): string {
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
            'words' => $this->searchTerm,
            'count' => $count,
            'url' => $url->relative(),
            'key' => ($what == 'archive') ? 'back_to_archive' : 'search_show_all',
        ]);
    }

    private function renderArticle(Request $request, int $id): Response
    {
        $article = $this->finder->findById($id);
        if (isset($article) && $request->admin() && $article->status > 0) {
            $this->db->recordPageView($id);
        }
        if (isset($article) && ($request->admin() || $article->status > 0)) {
            return $this->doRenderArticle($request, $article);
        }
        return new Response;
    }

    private function doRenderArticle(Request $request, FullArticle $article): Response
    {
        $teaser = trim(html_entity_decode(strip_tags($article->teaser), ENT_COMPAT, 'UTF-8'));
        $response = (new Response)
            ->withTitle($this->pages->headingOf($request->page()) . " – " . $article->title)
            ->withDescription(Util::shortenText($teaser));
        if ($article->status === 2) {
            $params = array('realblog_year' => (string) $this->year);
        } else {
            $params = array('realblog_page' => (string) $this->getPage($request));
        }

        $bridge = ucfirst($this->conf['comments_plugin']) . '\\RealblogBridge';

        $data = [
            'title' => $article->title,
            'heading' => $this->conf['heading_level'],
            'heading_above_meta' => $this->conf['heading_above_meta'],
            'is_admin' => $request->admin(),
            'wants_comments' => $this->wantsComments(),
            'back_text' => $article->status === 2 ? 'archiv_back' : 'blog_back',
            'back_url' => $request->url()->withParams($params)->relative(),
        ];
        if ($this->searchTerm) {
            $params['realblog_search'] = $this->searchTerm;
            $data['back_to_search_url'] = $request->url()->withParams($params)->relative();
        }
        $data['edit_url'] = $request->url()->withPage("realblog")
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
        return $response->withOutput($this->view->render('article', $data));
    }

    private function wantsComments(): bool
    {
        return $this->conf['comments_plugin']
            && class_exists(ucfirst($this->conf['comments_plugin']) . '\\RealblogBridge');
    }

    private function getPage(Request $request): int
    {
        if ($request->admin() && $request->edit()) {
            if (isset($_GET['realblog_page'])) {
                $page = max((int) ($_GET['realblog_page'] ?? 1), 1);
                $_COOKIE['realblog_page'] = $page;
                setcookie('realblog_page', (string) $page, 0, CMSIMPLE_ROOT);
            } else {
                $page = max((int) ($_COOKIE['realblog_page'] ?? 1), 1);
            }
        } else {
            $page = max((int) ($_GET['realblog_page'] ?? 1), 1);
        }
        return $page;
    }

    private function allArchivedPosts(Request $request, bool $showSearch): string
    {
        $html = '';
        if ($showSearch) {
            $html .= $this->renderSearchForm($request->url());
        }

        if ($this->searchTerm) {
            $articles = $this->finder->findArchivedArticlesContaining($this->searchTerm);
            $articleCount = count($articles);
            $html .= $this->renderSearchResults($request->url(), 'archive', $articleCount);
        } else {
            $articles = array();
        }

        $html .= $this->renderArchive($request, $articles);
        return $html;
    }

    /** @param list<Article> $articles */
    private function renderArchive(Request $request, array $articles): string
    {
        if (!$this->searchTerm) {
            $year = $this->year;
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
            return $this->renderArchivedArticles($request, $articles, false, $back, $next);
        } else {
            return $this->renderArchivedArticles($request, $articles, true, null, null);
        }
    }

    /** @param list<Article> $articles */
    private function renderArchivedArticles(
        Request $request,
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
                    'realblog_id' => $article->id,
                    'realblog_year' => date('Y', $article->date),
                    'realblog_search' => $_GET['realblog_search'] ?? "",
                ];
                $groupRecords[] = [
                    "title" => $article->title,
                    "date" => $this->view->date($article->date),
                    "url" => $request->url()->withParams($params)->relative(),
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
            'year' => $this->year,
        ];
        if ($back) {
            $data['backUrl'] = $request->url()->withParams(['realblog_year' => (string) $back])->relative();
        }
        if ($next) {
            $data['nextUrl'] = $request->url()->withParams(['realblog_year' => (string) $next])->relative();
        }
        return $this->view->render('archive', $data);
    }
}
