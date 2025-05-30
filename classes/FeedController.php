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
use Realblog\Value\Article;

class FeedController
{
    /** @var string */
    private $imageFolder;

    /** @var array<string,string> */
    private $conf;

    /** @var Finder */
    private $finder;

    /** @var Pages */
    private $pages;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        string $imageFolder,
        array $conf,
        Finder $finder,
        Pages $pages,
        View $view
    ) {
        $this->imageFolder = $imageFolder;
        $this->conf = $conf;
        $this->finder = $finder;
        $this->pages = $pages;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->conf["rss_enabled"] || $request->get("function") !== "realblog_feed") {
            return Response::create();
        }
        $count = (int) $this->conf["rss_entries"];
        $logo = $this->imageFolder . $this->conf["rss_logo"];
        return Response::create("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $this->view->render("feed", [
            "url" => $request->url()->page($this->conf["rss_page"])->absolute(),
            "managing_editor" => $this->conf["rss_editor"],
            "has_logo" => (bool) $this->conf["rss_logo"],
            "image_url" => $request->url()->path($logo)->absolute(),
            "articles" => $this->articleRecords($request->url(), $this->finder->findFeedableArticles($count)),
        ]))->withContentType("application/xml; charset=UTF-8");
    }

    /**
     * @param list<Article> $articles
     * @return list<array{title:string,url:string,teaser:string,date:string}>
     */
    private function articleRecords(Url $url, array $articles): array
    {
        $records = [];
        foreach ($articles as $article) {
            $records[] = [
                "title" => $article->title,
                "url" => $url->page("")->with("function", "realblog_article")
                    ->with("realblog_id", (string) $article->id)->absolute(),
                "teaser" => $this->pages->evaluateScripting($article->teaser),
                "date" => (string) date("r", $article->date),
            ];
        }
        return $records;
    }
}
