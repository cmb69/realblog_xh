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

class ArchiveController extends MainController
{
    /**
     * @param array<string,string> $config
     * @param array<string,string> $text
     * @param bool $showSearch
     */
    public function __construct(array $config, array $text, $showSearch = false)
    {
        parent::__construct($config, $text, $showSearch);
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        $html = '';
        if ($this->showSearch) {
            $html .= $this->renderSearchForm();
        }

        if ($this->searchTerm) {
            $articles = Finder::findArchivedArticlesContaining($this->searchTerm);
            $articleCount = count($articles);
            $html .= $this->renderSearchResults('archive', $articleCount);
        } else {
            $articles = array();
        }

        $html .= $this->renderArchive($articles);
        return $html;
    }

    /**
     * @return string
     */
    private function renderArchive(array $articles)
    {
        if (!$this->searchTerm) {
            $year = $this->year;
            $years = Finder::findArchiveYears();
            $key = array_search($year, $years);
            if ($key === false) {
                $key = count($years) - 1;
                $year = $years[$key];
            }
            $back = ($key > 0) ? $years[(int) $key - 1] : null;
            $next = ($key < count($years) - 1) ? $years[(int) $key + 1] : null;
            $articles = Finder::findArchivedArticlesInPeriod(
                mktime(0, 0, 0, 1, 1, $year),
                mktime(0, 0, 0, 1, 1, $year + 1)
            );
            return $this->renderArchivedArticles($articles, false, $back, $next);
        } else {
            return $this->renderArchivedArticles($articles, true, null, null);
        }
    }

    /**
     * @param bool $isSearch
     * @param int|null $back
     * @param int|null $next
     * @return string
     */
    private function renderArchivedArticles(array $articles, $isSearch, $back, $next)
    {
        global $su;

        $data = [
            'isSearch' => $isSearch,
            'articles' => $articles,
            'heading' => $this->config['heading_level'],
            'year' => $this->year,
            'url' => /** @return string */ function (stdClass $article) {
                global $su;

                return Realblog::url(
                    $su,
                    array(
                        'realblog_id' => $article->id,
                        'realblog_year' => date('Y', $article->date),
                        'realblog_search' => filter_input(INPUT_GET, 'realblog_search')
                    )
                );
            },
            'formatDate' => /** @return string */ function (stdClass $article) {
                return (string) date($this->text['date_format'], $article->date);
            },
            'yearOf' => /** @return string */ function (stdClass $article) {
                return (string) date('Y', $article->date);
            },
            'monthOf' => /** @return string */ function (stdClass $article) {
                return (string) date('n', $article->date);
            },
            'monthName' =>
            /**
             * @param int $month
             * @return string
             */
            function ($month) {
                $monthNames = explode(',', $this->text['date_months']);
                return $monthNames[$month - 1];
            },
        ];
        if ($back) {
            $data['backUrl'] = Realblog::url($su, array('realblog_year' => $back));
        }
        if ($next) {
            $data['nextUrl'] = Realblog::url($su, array('realblog_year' => $next));
        }
        return (new View('archive'))->render($data);
    }

    /**
     * @param int $id
     * @return string|null
     */
    public function showArticleAction($id)
    {
        return $this->renderArticle($id);
    }
}
