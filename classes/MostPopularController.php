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

class MostPopularController
{
    /** @var array<string,string> */
    private $config;

    /** @var string */
    private $pageUrl;

    /** @var list<string> */
    private $urls;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /**
     * @param array<string,string> $config
     * @param string $pageUrl
     * @param list<string> $urls
     */
    public function __construct(array $config, $pageUrl, $urls, Finder $finder, View $view)
    {
        $this->config = $config;
        $this->pageUrl = $pageUrl;
        $this->urls = $urls;
        $this->finder = $finder;
        $this->view = $view;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        if (!in_array($this->pageUrl, $this->urls) || $this->config['links_visible'] <= 0) {
            return "";
        }
        $pageUrl = $this->pageUrl;
        $data = [
            'articles' => $this->finder->findMostPopularArticles((int) $this->config['links_visible']),
            'heading' => $this->config['heading_level'],
            'url' => /** @return string */ function (MostPopularArticle $article) use ($pageUrl) {
                return Plugin::url($pageUrl, array('realblog_id' => $article->id));
            },
        ];
        return $this->view->render('most-popular', $data);
    }
}
