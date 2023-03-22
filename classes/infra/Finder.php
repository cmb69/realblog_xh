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

namespace Realblog\Infra;

use Realblog\Infra\DB;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use Realblog\Value\MostPopularArticle;

class Finder
{
    /** @var DB */
    private $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    /**
     * @return list<Article>
     */
    public function findArticles(
        int $status,
        int $limit,
        int $offset = 0,
        int $order = -1,
        string $category = 'all',
        ?string $search = null
    ): array {
        if ($order === -1) {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }
        $categoryClause = ($category !== 'all')
            ? 'AND categories LIKE :category'
            : '';
        $searchClause = isset($search)
            ? 'AND (title LIKE :search OR body LIKE :search)'
            : '';
        $sql = <<<SQL
SELECT id, date, status, categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles
    WHERE status = :status $categoryClause $searchClause
    ORDER BY date $order, id $order
    LIMIT $limit OFFSET $offset
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $statement->bindValue(':category', "%,$category,%", SQLITE3_TEXT);
        $statement->bindValue(':search', "%$search%", SQLITE3_TEXT);
        $result = $statement->execute();
        assert($result !== false);
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /** @return list<Article> */
    public function findArchivedArticlesInPeriod(int $start, int $end): array
    {
        $sql = <<<'SQL'
SELECT id, date, status, categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles
    WHERE status = :status AND date >= :start AND date < :end
    ORDER BY date DESC, id DESC
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':status', Article::ARCHIVED, SQLITE3_INTEGER);
        $statement->bindValue(':start', $start, SQLITE3_INTEGER);
        $statement->bindValue(':end', $end, SQLITE3_INTEGER);
        $result = $statement->execute();
        assert($result !== false);
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /** @return list<int> */
    public function findArchiveYears(): array
    {
        $sql = <<<'SQL'
SELECT DISTINCT strftime('%Y', date, 'unixepoch') AS year
    FROM articles WHERE status = :status ORDER BY year
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':status', Article::ARCHIVED, SQLITE3_INTEGER);
        $res = $statement->execute();
        assert($res !== false);
        $years = array();
        while (($record = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
            $years[] = (int) $record['year'];
        }
        return $years;
    }

    /** @return list<Article> */
    public function findArchivedArticlesContaining(string $search): array
    {
        $sql = <<<'SQL'
SELECT id, date, status, categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles
    WHERE (title LIKE :text OR body LIKE :text) AND status = :status
    ORDER BY date DESC, id DESC
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':text', '%' . $search . '%', SQLITE3_TEXT);
        $statement->bindValue(':status', Article::ARCHIVED, SQLITE3_INTEGER);
        $result = $statement->execute();
        assert($result !== false);
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    public function countArticlesWithStatus(int $states, string $category = 'all', ?string $search = null): int
    {
        $db = $this->db->getConnection();
        $whereClause = $this->statesToWhereClause($states);
        $categoryClause = ($category !== 'all')
            ? 'AND categories LIKE :category'
            : '';
        $searchClause = isset($search)
            ? 'AND (title LIKE :search OR body LIKE :search)'
            : '';
        $sql = <<<SQL
SELECT COUNT(*) AS count FROM articles WHERE $whereClause $categoryClause $searchClause
SQL;
        $statement = $db->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':category', "%,$category,%", SQLITE3_TEXT);
        $statement->bindValue(':search', "%$search%", SQLITE3_TEXT);
        $result = $statement->execute();
        assert($result !== false);
        $record = $result->fetchArray(SQLITE3_ASSOC);
        assert($record !== false);
        return $record['count'];
    }

    /** @return list<Article>*/
    public function findArticlesWithStatus(int $states, int $limit, int $offset): array
    {
        $whereClause = $this->statesToWhereClause($states);
        $sql = <<<SQL
SELECT id, date, status, trim(categories, ',') as categories, title, teaser,
        length(body) AS hasBody, feedable, commentable
    FROM articles WHERE $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset
SQL;
        $connection = $this->db->getConnection();
        $result = $connection->query($sql);
        assert($result !== false);
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    private function statesToWhereClause(int $states): string
    {
        if ($states === 0) {
            return "1 = 0";
        }
        $result = [];
        for ($i = Article::FIRST_STATE; $i <= Article::LAST_STATE; $i++) {
            if ($states & (1 << $i)) {
                $result[] = $i;
            }
        }
        return sprintf("status IN (%s)", implode(", ", $result));
    }

    /** @return list<Article> */
    public function findFeedableArticles(int $count)
    {
        $sql = <<<SQL
SELECT id, date, status, categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles WHERE status = :status AND feedable = :feedable ORDER BY date DESC, id DESC
    LIMIT $count
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':status', Article::PUBLISHED, SQLITE3_INTEGER);
        $statement->bindValue(':feedable', 1, SQLITE3_INTEGER);
        $result = $statement->execute();
        assert($result !== false);
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /** @return list<MostPopularArticle> */
    public function findMostPopularArticles(int $limit): array
    {
        $sql = <<<SQL
SELECT articles.id, articles.title, COUNT(*) AS page_views
    FROM articles LEFT JOIN page_views ON articles.id = page_views.article_id
    GROUP BY articles.id
    ORDER BY page_views DESC
    LIMIT $limit
SQL;
        $connection = $this->db->getConnection();
        $result = $connection->query($sql);
        assert($result !== false);
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new MostPopularArticle(...$record);
        }
        return $objects;
    }

    /** @return list<string> */
    public function findAllCategories(): array
    {
        $db = $this->db->getConnection();
        $statement = $db->prepare('SELECT DISTINCT categories FROM articles');
        assert($statement !== false);
        $result = $statement->execute();
        assert($result !== false);
        $categories = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            if ($record['categories'] !== ',,') {
                $categories = array_merge($categories, explode(',', trim($record['categories'], ',')));
            }
        }
        $categories = array_unique($categories);
        sort($categories);
        return $categories;
    }

    public function findById(int $id): ?FullArticle
    {
        $db = $this->db->getConnection();
        $statement = $db->prepare('SELECT * FROM articles WHERE id = :id');
        assert($statement !== false);
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();
        assert($result !== false);
        $record = $result->fetchArray(SQLITE3_NUM);
        if ($record !== false) {
            return new FullArticle(...$record);
        } else {
            return null;
        }
    }
}
