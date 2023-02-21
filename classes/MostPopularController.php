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

use Realblog\Infra\Finder;
use Realblog\Infra\View;
use Realblog\Value\MostPopularArticle;

class MostPopularController
{
    /** @var array<string,string> */
    private $config;

    /** @var list<string> */
    private $urls;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /**
     * @param array<string,string> $config
     * @param list<string> $urls
     */
    public function __construct(array $config, $urls, Finder $finder, View $view)
    {
        $this->config = $config;
        $this->urls = $urls;
        $this->finder = $finder;
        $this->view = $view;
    }

    public function __invoke(string $pageUrl): string
    {
        if (!in_array($pageUrl, $this->urls) || $this->config['links_visible'] <= 0) {
            return "";
        }
        $articles = $this->finder->findMostPopularArticles((int) $this->config['links_visible']);
        $records = [];
        foreach ($articles as $article) {
            $record = get_object_vars($article);
            $record["url"] = Plugin::url($pageUrl, ["realblog_id" => (string) $article->id]);
            $records[] = $record;
        }
        $data = [
            'articles' => $records,
            'heading' => $this->config['heading_level'],
        ];
        return $this->view->render('most-popular', $data);
    }
}
