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

use Realblog\Infra\Request;
use Realblog\Infra\Response;
use Realblog\Infra\Url;
use Realblog\Value\Article;

class ArchiveController extends MainController
{
    public function __invoke(Request $request, bool $showSearch): Response
    {
        if (isset($_GET["realblog_id"])) {
            return $this->showArticleAction($request->url(), max((int) ($_GET["realblog_id"] ?? 1), 1));
        } else {
            return (new Response)->withOutput($this->defaultAction($request, $showSearch));
        }
    }

    /**
     * @return string
     */
    private function defaultAction(Request $request, bool $showSearch)
    {
        $html = '';
        if ($showSearch) {
            $html .= $this->renderSearchForm($request->url());
        }

        if ($this->searchTerm) {
            $articles = $this->finder->findArchivedArticlesContaining($this->searchTerm);
            $articleCount = count($articles);
            $html .= $this->renderSearchResults($request->url(), 'archive', $articleCount);
        } else {
            $articles = array();
        }

        $html .= $this->renderArchive($request, $articles);
        return $html;
    }

    /**
     * @param list<Article> $articles
     * @return string
     */
    private function renderArchive(Request $request, array $articles)
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
            return $this->renderArchivedArticles($request, $articles, false, $back, $next);
        } else {
            return $this->renderArchivedArticles($request, $articles, true, null, null);
        }
    }

    /**
     * @param Article[] $articles
     * @param bool $isSearch
     * @param int|null $back
     * @param int|null $next
     * @return string
     */
    private function renderArchivedArticles(Request $request, array $articles, $isSearch, $back, $next)
    {
        $records = [];
        foreach ($this->groupArticlesByMonth($articles) as $group) {
            $groupRecords = [];
            foreach ($group as $article) {
                $params = [
                    'realblog_id' => $article->id,
                    'realblog_year' => date('Y', $article->date),
                    'realblog_search' => $_GET['realblog_search'] ?? "",
                ];
                $groupRecords[] = [
                    "title" => $article->title,
                    "date" => $this->view->date($article->date),
                    "url" => $request->url()->withParams($params)->relative(),
                    "year" => idate('Y', $article->date),
                    "month" => idate('n', $article->date) - 1,
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
            $data['backUrl'] = $request->url()->withParams(['realblog_year' => (string) $back])->relative();
        }
        if ($next) {
            $data['nextUrl'] = $request->url()->withParams(['realblog_year' => (string) $next])->relative();
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

    public function showArticleAction(Url $url, int $id): Response
    {
        return $this->renderArticle($url, $id);
    }
}
