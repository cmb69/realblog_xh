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

namespace Realblog\Logic;

use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class Util
{
    /**
     * @param list<Article> $articles
     * @return list<array{year:int,month:int,articles:list<Article>}>
     */
    public static function groupArticlesByMonth(array $articles): array
    {
        $currentYear = $currentMonth = null;
        $groups = $currentGroup = [];
        foreach ($articles as $article) {
            $year = (int) idate('Y', $article->date);
            $month = (int) idate('n', $article->date);
            if ($year !== $currentYear || $month !== $currentMonth) {
                if (!empty($currentGroup)) {
                    assert($currentYear !== null);
                    assert($currentMonth !== null);
                    $groups[] = [
                        "year" => $currentYear,
                        "month" => $currentMonth,
                        "articles" => $currentGroup
                    ];
                }
                $currentYear = $year;
                $currentMonth = $month;
                $currentGroup = [];
            }
            $currentGroup[] = $article;
        }
        if (!empty($currentGroup)) {
            assert($currentYear !== null);
            assert($currentMonth !== null);
            $groups[] = [
                "year" => $currentYear,
                "month" => $currentMonth,
                "articles" => $currentGroup,
            ];
        }
        return $groups;
    }

    public static function shortenText(string $text): string
    {
        if (utf8_strlen($text) <= 150) {
            return $text;
        }
        if (preg_match('/^.{1,150}\b/su', $text, $matches)) {
            return $matches[0] . '…';
        }
        return utf8_substr($text, 0, 150) . '…';
    }

    /** @return array{int,int,int} */
    public static function paginationOffset(int $itemCount, int $itemsPerPage, int $page): array
    {
        $pageCount = (int) ceil($itemCount / $itemsPerPage);
        $page = min(max($page, 1), $pageCount);
        $offset = ($page - 1) * $itemsPerPage;
        return [$offset, $pageCount, $page];
    }

    /**
     * @param int<2,max> $count
     * @return list<int|null>
     */
    public static function gatherPages(int $page, int $count, int $radius): array
    {
        $pages = array(1);
        if ($page - $radius > 1 + 1) {
            $pages[] = null;
        }
        for ($i = $page - $radius; $i <= $page + $radius; $i++) {
            if ($i > 1 && $i < $count) {
                $pages[] = $i;
            }
        }
        if ($page + $radius < $count - 1) {
            $pages[] = null;
        }
        $pages[] = $count;
        return $pages;
    }

    /** @return list<array{string}> */
    public static function validateArticle(FullArticle $article): array
    {
        $errors = [];
        if ($article->id < 0) {
            $errors[] = ["error_id"];
        }
        if ($article->version < 0) {
            $errors[] = ["error_version"];
        }
        if ($article->date <= 0) {
            $errors[] = ["error_date"];
        }
        if ($article->publishingDate <= 0) {
            $errors[] = ["error_publishing_date"];
        }
        if ($article->archivingDate <= 0) {
            $errors[] = ["error_archiving_date"];
        }
        if ($article->status < Article::FIRST_STATE || $article->status > Article::LAST_STATE) {
            $errors[] = ["error_status"];
        }
        if ($article->title === "") {
            $errors[] = ["error_title"];
        }
        return $errors;
    }
}
