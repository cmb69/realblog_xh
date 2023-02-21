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
use Realblog\Infra\ScriptEvaluator;
use Realblog\Infra\View;
use Realblog\Value\FullArticle;

abstract class MainController
{
    /** @var array<string,string> */
    protected $config;

    /** @var array<string,string> */
    protected $text;

    /** @var DB */
    protected $db;

    /** @var Finder */
    protected $finder;

    /** @var View */
    protected $view;

    /** @var ScriptEvaluator|null */
    protected $scriptEvaluator;

    /** @var string */
    protected $searchTerm;

    /** @var int */
    protected $year;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $text
     */
    public function __construct(
        array $config,
        array $text,
        DB $db,
        Finder $finder,
        View $view,
        ScriptEvaluator $scriptEvaluator
    ) {
        $this->config = $config;
        $this->text = $text;
        $this->db = $db;
        $this->finder = $finder;
        $this->view = $view;
        $this->scriptEvaluator = $scriptEvaluator;
        $input = filter_var_array(
            $_GET,
            array(
                'realblog_search' => FILTER_DEFAULT,
                'realblog_year' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => array('default' => (int) date('Y'))
                )
            )
        );
        $this->searchTerm = $input['realblog_search'];
        $this->year = $input['realblog_year'];
    }

    /**
     * @return string
     */
    protected function renderSearchForm()
    {
        global $su, $sn;

        $data = [
            'actionUrl' => $sn,
            'pageUrl' => $su,
        ];
        return $this->view->render('search-form', $data);
    }

    /**
     * @param string $what
     * @param int $count
     * @return string
     */
    protected function renderSearchResults($what, $count)
    {
        global $su;

        $data = [
            'words' => $this->searchTerm,
            'count' => $count,
            'url' => Plugin::url($su),
            'key' => ($what == 'archive') ? 'back_to_archive' : 'search_show_all',
        ];
        return $this->view->render('search-results', $data);
    }

    /**
     * @param int $id
     * @return string|void
     */
    protected function renderArticle($id)
    {
        $article = $this->finder->findById($id);
        if (isset($article) && (defined("XH_ADM") && !XH_ADM) && $article->status > 0) {
            $this->db->recordPageView($id);
        }
        if (isset($article) && ((defined("XH_ADM") && XH_ADM) || $article->status > 0)) {
            return $this->doRenderArticle($article);
        }
    }

    /**
     * @return string
     */
    private function doRenderArticle(FullArticle $article)
    {
        global $sn, $su, $h, $s, $title, $description;

        $title .= $h[$s] . " \xE2\x80\x93 " . $article->title;
        $description = $this->getDescription($article);
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
            'back_text' => $article->status === 2 ? $this->text['archiv_back'] : $this->text['blog_back'],
            'back_url' => Plugin::url($su, $params),
        ];
        if ($this->searchTerm) {
            $params['realblog_search'] = $this->searchTerm;
            $data['back_to_search_url'] = Plugin::url($su, $params);
        }
        $data['edit_url'] = "$sn?&realblog&admin=plugin_main"
            . "&action=edit&realblog_id={$article->id}";
        if ($this->wantsComments()) {
            /** @var class-string $bridge */
            $commentsUrl = $bridge::getEditUrl("realblog{$article->id}");
            if ($commentsUrl !== false) {
                $data['edit_comments_url'] = $commentsUrl;
            }
            $data['comment_count'] = $bridge::count("realblog{$article->id}");
            $data["comments"] = $bridge::handle("realblog{$article->id}");
        }
        $data['date'] = date($this->text['date_format'], $article->date);
        $categories = explode(',', trim($article->categories, ','));
        $data['categories'] = implode(', ', $categories);
        if ($this->config['show_teaser']) {
            $story = '<div class="realblog_teaser">' . $article->teaser . '</div>' . $article->body;
        } else {
            $story = ($article->body != '') ? $article->body : $article->teaser;
        }
        $data['story'] = $this->scriptEvaluator->evaluate($story);
        return $this->view->render('article', $data);
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
