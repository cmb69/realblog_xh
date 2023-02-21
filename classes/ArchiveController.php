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

use Realblog\Value\Article;

class ArchiveController extends MainController
{
    public function __invoke(bool $showSearch): string
    {
        if (isset($_GET["realblog_id"])) {
            return (string) $this->showArticleAction(max((int) ($_GET["realblog_id"] ?? 1), 1));
        } else {
            return $this->defaultAction($showSearch);
        }
    }

    /**
     * @return string
     */
    private function defaultAction(bool $showSearch)
    {
        $html = '';
        if ($showSearch) {
            $html .= $this->renderSearchForm();
        }

        if ($this->searchTerm) {
            $articles = $this->finder->findArchivedArticlesContaining($this->searchTerm);
            $articleCount = count($articles);
            $html .= $this->renderSearchResults('archive', $articleCount);
        } else {
            $articles = array();
        }

        $html .= $this->renderArchive($articles);
        return $html;
    }

    /**
     * @param list<Article> $articles
     * @return string
     */
    private function renderArchive(array $articles)
    {
        if (!$this->searchTerm) {
            $year = $this->year;
            $years = $this->finder->findArchiveYears();
            $key = array_search($year, $years);
            if ($key === false) {
                $key = count($years) - 1;
                $year = $years[$key];
            }
            $back = ($key > 0) ? $years[(int) $key - 1] : null;
            $next = ($key < count($years) - 1) ? $years[(int) $key + 1] : null;
            $articles = $this->finder->findArchivedArticlesInPeriod(
                (int) mktime(0, 0, 0, 1, 1, $year),
                (int) mktime(0, 0, 0, 1, 1, $year + 1)
            );
            return $this->renderArchivedArticles($articles, false, $back, $next);
        } else {
            return $this->renderArchivedArticles($articles, true, null, null);
        }
    }

    /**
     * @param Article[] $articles
     * @param bool $isSearch
     * @param int|null $back
     * @param int|null $next
     * @return string
     */
    private function renderArchivedArticles(array $articles, $isSearch, $back, $next)
    {
        global $su;

        $monthNames = explode(',', $this->text['date_months']);
        $records = [];
        foreach ($this->groupArticlesByMonth($articles) as $group) {
            $groupRecords = [];
            foreach ($group as $article) {
                $url = Plugin::url(
                    $su,
                    [
                        'realblog_id' => $article->id,
                        'realblog_year' => date('Y', $article->date),
                        'realblog_search' => $_GET['realblog_search'] ?? "",
                    ]
                );
                $groupRecords[] = [
                    "title" => $article->title,
                    "date" => (string) date($this->text['date_format'], $article->date),
                    "url" => $url,
                    "year" => idate('Y', $article->date),
                    "month" => $monthNames[idate('n', $article->date) - 1],
                ];
            }
            $records[] = $groupRecords;
        }

        $data = [
            'isSearch' => $isSearch,
            'articles' => $records,
            'heading' => $this->config['heading_level'],
            'year' => $this->year,
        ];
        if ($back) {
            $data['backUrl'] = Plugin::url($su, array('realblog_year' => (string) $back));
        }
        if ($next) {
            $data['nextUrl'] = Plugin::url($su, array('realblog_year' => (string) $next));
        }
        return $this->view->render('archive', $data);
    }

    /**
     * @param Article[] $articles
     * @return Article[][]
     */
    private function groupArticlesByMonth(array $articles)
    {
        $currentYear = $currentMonth = null;
        $groups = $currentGroup = [];
        foreach ($articles as $article) {
            $year = (int) date('Y', $article->date);
            $month = (int) date('n', $article->date);
            if ($year !== $currentYear || $month !== $currentMonth) {
                $currentYear = $year;
                $currentMonth = $month;
                if (!empty($currentGroup)) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [];
            } else {
            }
            $currentGroup[] = $article;
        }
        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }
        return $groups;
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
