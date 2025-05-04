<?php

/**
 * Copyright 2017-2023 Christoph M. Becker
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

use Plib\Response;
use Plib\View;
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Infra\Request;
use Realblog\Value\MostPopularArticle;
use Realblog\Value\Url;

class MostPopularController
{
    /** @var array<string,string> */
    private $conf;

    /** @var Pages */
    private $pages;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, Pages $pages, Finder $finder, View $view)
    {
        $this->conf = $conf;
        $this->pages = $pages;
        $this->finder = $finder;
        $this->view = $view;
    }

    public function __invoke(Request $request, string $pageUrl): Response
    {
        if (!$this->pages->hasPageWithUrl($pageUrl) || $this->conf["links_visible"] <= 0) {
            return Response::create();
        }
        $articles = $this->finder->findMostPopularArticles((int) $this->conf["links_visible"]);
        return Response::create($this->view->render("most_popular", [
            "articles" => $this->articleRecords($request->url(), $articles, $pageUrl),
            "heading" => $this->conf["heading_level"],
        ]));
    }

    /**
     * @param list<MostPopularArticle> $articles
     * @return list<array{id:int,title:string,page_views:int,url:string}>
     */
    private function articleRecords(Url $url, array $articles, string $pageUrl): array
    {
        $records = [];
        foreach ($articles as $article) {
            $records[] = [
                "id" => $article->id,
                "title" => $article->title,
                "page_views" => $article->pageViews,
                "url" => $url->withPage($pageUrl)->with("realblog_id", (string) $article->id)->relative(),
            ];
        }
        return $records;
    }
}
