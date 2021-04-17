<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
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

use stdClass;

class LinkController
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $text;

    /** @var string */
    private $pageUrl;

    /** @var bool */
    private $showTeaser;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $text
     * @param string $pageUrl
     * @param bool $showTeaser
     */
    public function __construct(array $config, array $text, $pageUrl, $showTeaser, Finder $finder, View $view)
    {
        $this->config = $config;
        $this->text = $text;
        $this->pageUrl = $pageUrl;
        $this->showTeaser = $showTeaser;
        $this->finder = $finder;
        $this->view = $view;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        global $u;

        if (!in_array($this->pageUrl, $u) || $this->config['links_visible'] <= 0) {
            return "";
        }
        $pageUrl = $this->pageUrl;
        $data = [
            'articles' => $this->finder->findArticles(1, (int) $this->config['links_visible']),
            'heading' => $this->config['heading_level'],
            'formatDate' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) {
                return date($this->text['date_format'], $article->date);
            },
            'url' =>
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) use ($pageUrl) {
                return Realblog::url($pageUrl, array('realblog_id' => $article->id));
            },
            'showTeaser' => $this->showTeaser,
            'teaser' =>
            /**
             * @param stdClass $article
             * @return HtmlString
             */
            function ($article) {
                return new HtmlString(evaluate_scripting($article->teaser));
            },
        ];
        return $this->view->render('latest', $data);
    }
}
