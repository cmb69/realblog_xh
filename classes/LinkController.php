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
use Realblog\Infra\ScriptEvaluator;
use Realblog\Infra\View;
use Realblog\Value\Article;
use Realblog\Value\HtmlString;

class LinkController
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $text;

    /** @var string */
    private $pageUrl;

    /** @var list<string> */
    private $urls;

    /** @var bool */
    private $showTeaser;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /** @var ScriptEvaluator */
    private $scriptEvaluator;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $text
     * @param string $pageUrl
     * @param list<string> $urls
     * @param bool $showTeaser
     */
    public function __construct(
        array $config,
        array $text,
        $pageUrl,
        $urls,
        $showTeaser,
        Finder $finder,
        View $view,
        ScriptEvaluator $scriptEvaluator
    ) {
        $this->config = $config;
        $this->text = $text;
        $this->pageUrl = $pageUrl;
        $this->urls = $urls;
        $this->showTeaser = $showTeaser;
        $this->finder = $finder;
        $this->view = $view;
        $this->scriptEvaluator = $scriptEvaluator;
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
            'articles' => $this->finder->findArticles(1, (int) $this->config['links_visible']),
            'heading' => $this->config['heading_level'],
            'formatDate' => /** @return string */ function (Article $article) {
                return date($this->text['date_format'], $article->date);
            },
            'url' => /** @return string */ function (Article $article) use ($pageUrl) {
                return Plugin::url($pageUrl, array('realblog_id' => (string) $article->id));
            },
            'showTeaser' => $this->showTeaser,
            'teaser' => /** @return HtmlString */ function (Article $article) {
                return new HtmlString($this->scriptEvaluator->evaluate($article->teaser));
            },
        ];
        return $this->view->render('latest', $data);
    }
}
