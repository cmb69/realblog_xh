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

use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Infra\Request;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\Html;
use Realblog\Value\Response;
use Realblog\Value\Url;

class LinkController
{
    /** @var array<string,string> */
    private $conf;

    /** @var Pages */
    private $pages;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /**
     * @param array<string,string> $conf
     */
    public function __construct(
        array $conf,
        Pages $pages,
        Finder $finder,
        View $view
    ) {
        $this->conf = $conf;
        $this->pages = $pages;
        $this->finder = $finder;
        $this->view = $view;
        $this->pages = $pages;
    }

    public function __invoke(Request $request, string $pageUrl, bool $showTeaser = false): Response
    {
        if (!$this->pages->hasPageWithUrl($pageUrl) || $this->conf["links_visible"] <= 0) {
            return Response::create();
        }
        $articles = $this->finder->findArticles(1, (int) $this->conf["links_visible"]);
        return Response::create($this->view->render("latest", [
            "articles" => $this->articleRecords($request->url(), $articles, $pageUrl),
            "heading" => $this->conf["heading_level"],
            "show_teaser" => $showTeaser,
        ]));
    }

    /**
     * @param list<Article> $articles
     * @return list<array{title:string,date:string,url:string,teaser:Html}>
     */
    private function articleRecords(Url $url, array $articles, string $pageUrl): array
    {
        $records = [];
        foreach ($articles as $article) {
            $records[] = [
                "title" => $article->title,
                "date" => $this->view->date($article->date),
                "url" => $url->withPage($pageUrl)
                    ->with("realblog_id", (string) $article->id)->relative(),
                "teaser" => Html::of($this->pages->evaluateScripting($article->teaser)),
            ];
        }
        return $records;
    }
}
