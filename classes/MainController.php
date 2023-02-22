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

use Realblog\Infra\DB;
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Infra\Response;
use Realblog\Infra\Url;
use Realblog\Infra\View;
use Realblog\Value\FullArticle;

abstract class MainController
{
    /** @var array<string,string> */
    protected $config;

    /** @var DB */
    protected $db;

    /** @var Finder */
    protected $finder;

    /** @var View */
    protected $view;

    /** @var Pages */
    protected $pages;

    /** @var string */
    protected $searchTerm;

    /** @var int */
    protected $year;

    /** @param array<string,string> $config */
    public function __construct(
        array $config,
        DB $db,
        Finder $finder,
        View $view,
        Pages $pages
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->finder = $finder;
        $this->view = $view;
        $this->pages = $pages;
        $this->searchTerm = $_GET['realblog_search'] ?? "";
        $this->year = (int) ($_GET['realblog_year'] ?? idate("Y"));
    }

    /**
     * @return string
     */
    protected function renderSearchForm(Url $url)
    {
        $data = [
            'actionUrl' => $url->withPage("")->relative(),
            'pageUrl' => $url->page(),
        ];
        return $this->view->render('search-form', $data);
    }

    /**
     * @param string $what
     * @param int $count
     * @return string
     */
    protected function renderSearchResults(Url $url, $what, $count)
    {
        $data = [
            'words' => $this->searchTerm,
            'count' => $count,
            'url' => $url->relative(),
            'key' => ($what == 'archive') ? 'back_to_archive' : 'search_show_all',
        ];
        return $this->view->render('search-results', $data);
    }

    protected function renderArticle(Url $url, int $id): Response
    {
        $article = $this->finder->findById($id);
        if (isset($article) && (defined("XH_ADM") && !XH_ADM) && $article->status > 0) {
            $this->db->recordPageView($id);
        }
        if (isset($article) && ((defined("XH_ADM") && XH_ADM) || $article->status > 0)) {
            return $this->doRenderArticle($url, $article);
        }
        return new Response;
    }

    private function doRenderArticle(Url $url, FullArticle $article): Response
    {
        global $s;

        $response = (new Response)
            ->withTitle($this->pages->headingOf($s) . " – " . $article->title)
            ->withDescription($this->getDescription($article));
        if ($article->status === 2) {
            $params = array('realblog_year' => (string) $this->year);
        } else {
            $params = array('realblog_page' => (string) Plugin::getPage());
        }

        $bridge = ucfirst($this->config['comments_plugin']) . '\\RealblogBridge';

        $data = [
            'title' => $article->title,
            'heading' => $this->config['heading_level'],
            'heading_above_meta' => $this->config['heading_above_meta'],
            'is_admin' => defined("XH_ADM") && XH_ADM,
            'wants_comments' => $this->wantsComments(),
            'back_text' => $article->status === 2 ? 'archiv_back' : 'blog_back',
            'back_url' => $url->withParams($params)->relative(),
        ];
        if ($this->searchTerm) {
            $params['realblog_search'] = $this->searchTerm;
            $data['back_to_search_url'] = $url->withParams($params)->relative();
        }
        $data['edit_url'] = $url->withPage("realblog")
            ->withParams(["admin" => "plugin_main", "action" => "edit", "realblog_id" => (string) $article->id])
            ->relative();
        if ($this->wantsComments()) {
            /** @var class-string $bridge */
            $commentsUrl = $bridge::getEditUrl("realblog{$article->id}");
            if ($commentsUrl !== false) {
                $data['edit_comments_url'] = $commentsUrl;
            }
            $data['comment_count'] = $bridge::count("realblog{$article->id}");
            $data["comments"] = $bridge::handle("realblog{$article->id}");
        }
        $data['date'] = $this->view->date($article->date);
        $categories = explode(',', trim($article->categories, ','));
        $data['categories'] = implode(', ', $categories);
        if ($this->config['show_teaser']) {
            $story = '<div class="realblog_teaser">' . $article->teaser . '</div>' . $article->body;
        } else {
            $story = ($article->body != '') ? $article->body : $article->teaser;
        }
        $data['story'] = $this->pages->evaluateScripting($story);
        return $response->withOutput($this->view->render('article', $data));
    }

    /**
     * @return string
     */
    private function getDescription(FullArticle $article)
    {
        $teaser = trim(html_entity_decode(strip_tags($article->teaser), ENT_COMPAT, 'UTF-8'));
        if (utf8_strlen($teaser) <= 150) {
            return $teaser;
        } elseif (preg_match('/.{0,150}\b/su', $teaser, $matches)) {
            return $matches[0] . '…';
        } else {
            return utf8_substr($teaser, 0, 150) . '…';
        }
    }

    /**
     * @return bool
     */
    private function wantsComments()
    {
        return $this->config['comments_plugin']
            && class_exists(ucfirst($this->config['comments_plugin']) . '\\RealblogBridge');
    }
}
