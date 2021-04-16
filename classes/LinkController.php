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

class LinkController extends AbstractController
{
    /** @var string */
    private $pageUrl;

    /** @var bool */
    private $showTeaser;

    /**
     * @param string $pageUrl
     * @param bool $showTeaser
     */
    public function __construct($pageUrl, $showTeaser)
    {
        parent::__construct();
        $this->pageUrl = $pageUrl;
        $this->showTeaser = $showTeaser;
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
        $view = new View('latest');
        $view->articles = Finder::findArticles(1, (int) $this->config['links_visible']);
        $view->heading = $this->config['heading_level'];
        $view->formatDate =
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) {
                global $plugin_tx;

                return date($plugin_tx['realblog']['date_format'], $article->date);
            };
        $pageUrl = $this->pageUrl;
        $view->url =
            /**
             * @param stdClass $article
             * @return string
             */
            function ($article) use ($pageUrl) {
                return Realblog::url($pageUrl, array('realblog_id' => $article->id));
            };
        $view->showTeaser = $this->showTeaser;
        $view->teaser =
            /**
             * @param stdClass $article
             * @return HtmlString
             */
            function ($article) {
                return new HtmlString(evaluate_scripting($article->teaser));
            };
        return $view->render();
    }
}
