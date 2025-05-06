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
use Plib\View;
use Realblog\Infra\DB;
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Logic\Util;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class ArticleController
{
    /** @var array<string,string> */
    private $conf;

    /** @var Finder */
    private $finder;

    /** @var DB */
    private $db;

    /** @var Pages */
    private $pages;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        Finder $finder,
        DB $db,
        Pages $pages,
        View $view
    ) {
        $this->conf = $conf;
        $this->finder = $finder;
        $this->db = $db;
        $this->pages = $pages;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->get("function") !== "realblog_article" || $request->get("realblog_id") === null) {
            return Response::create();
        }
        return $this->oneArticle($request, (int) $request->get("realblog_id"));
    }

    private function oneArticle(Request $request, int $id): Response
    {
        $article = $this->finder->findById($id);
        if ($article === null || (!$request->admin() && $article->status === Article::UNPUBLISHED)) {
            return Response::create($this->view->message("fail", "message_not_found"));
        }
        if (!$request->admin()) {
            $this->db->recordPageView($id);
        }
        return $this->renderArticle($request, $article);
    }

    private function renderArticle(Request $request, FullArticle $article): Response
    {
        $teaser = trim(html_entity_decode(strip_tags($article->teaser), ENT_COMPAT, "UTF-8"));
        if ($article->status === Article::ARCHIVED) {
            $url = $request->url()->with("realblog_year", $this->year($request));
        } else {
            $url = $request->url()->with("realblog_page", (string) $this->realblogPage($request));
        }
        $linkBack = $request->get("realblog_selected") !== null;
        if ($linkBack) {
            $url = $url->page($request->get("realblog_selected") ?? "");
            if ($request->get("realblog_page") !== null) {
                $url = $url->with("realblog_page", $request->get("realblog_page"));
            } elseif ($request->get("realblog_year") !== null) {
                $url = $url->with("realblog_year", $request->get("realblog_year"));
            }
            if ($request->get("realblog_search") !== null) {
                $url = $url->with("realblog_search", $request->get("realblog_search"));
            }
            $backUrl = $url->without("realblog_search")->relative();
            $searchTerm = $request->get("realblog_search") ?? "";
            if ($searchTerm !== "") {
                $backToSearchUrl = $url->with("realblog_search", $searchTerm)->relative();
            }
        } else {
            $url = null;
            $backUrl = null;
        }
        $editUrl = $request->url()->page("realblog")->with("admin", "plugin_main")
            ->with("action", "edit")->with("realblog_id", (string) $article->id)->relative();
        $bridge = ucfirst($this->conf["comments_plugin"]) . "\\RealblogBridge";
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
            "heading_above_meta" => $this->conf["heading_above_meta"],
            "is_admin" => $request->admin(),
            "wants_comments" => $this->conf["comments_plugin"] && class_exists($bridge),
            "back_text" => $article->status === 2 ? "archiv_back" : "blog_back",
            "back_url" => $backUrl,
            "back_to_search_url" => $backToSearchUrl ?? null,
            "edit_url" => $editUrl,
            "edit_comments_url" => !empty($commentsUrl) ? $commentsUrl : null,
            "comment_count" => class_exists($bridge) ? $bridge::count("realblog{$article->id}") : null,
            "comments" => class_exists($bridge) ? $bridge::handle("realblog{$article->id}") : null,
            "date" => date($this->view->text("date_format"), $article->date),
            "categories" => implode(", ", explode(",", trim($article->categories, ","))),
            "story" => $this->pages->evaluateScripting($story),
        ]))->withTitle($article->title)
            ->withDescription(Util::shortenText($teaser))
            ->withCanonicalParams(["realblog_id"]);
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
