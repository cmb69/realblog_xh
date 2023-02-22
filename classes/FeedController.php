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
use Realblog\Infra\Response;
use Realblog\Infra\View;

class FeedController
{
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
        array $conf,
        Finder $finder,
        Pages $pages,
        View $view
    ) {
        $this->conf = $conf;
        $this->finder = $finder;
        $this->pages = $pages;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        $count = (int) $this->conf['rss_entries'];
        $articles = $this->finder->findFeedableArticles($count);
        $records = [];
        foreach ($articles as $article) {
            $records[] = [
                "title" => $article->title,
                "url" => $request->url()->withPage($this->conf["rss_page"])
                    ->withParams(['realblog_id' => (string) $article->id])->absolute(),
                "teaser" => $this->pages->evaluateScripting($article->teaser),
                "date" => (string) date('r', $article->date),
            ];
        }
        $data = [
            'url' => $request->url()->withPage($this->conf['rss_page'])->absolute(),
            'managingEditor' => $this->conf['rss_editor'],
            'hasLogo' => (bool) $this->conf['rss_logo'],
            'imageUrl' => $request->url()->withPath($request->imageFolder() . $this->conf['rss_logo'])->absolute(),
            'articles' => $records,
        ];
        return (new Response)->withOutput(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . $this->view->render('feed', $data)
        );
    }
}
