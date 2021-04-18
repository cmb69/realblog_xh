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

class Finder
{
    /** @var DB */
    private $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $status
     * @param int $limit
     * @param int $offset
     * @param int $order
     * @param string $category
     * @param string|null $search
     * @return Article[]
     */
    public function findArticles($status, $limit, $offset = 0, $order = -1, $category = 'all', $search = null)
    {
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
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $statement->bindValue(':category', "%,$category,%", SQLITE3_TEXT);
        $statement->bindValue(':search', "%$search%", SQLITE3_TEXT);
        $result = $statement->execute();
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /**
     * @param int $start
     * @param int $end
     * @return Article[]
     */
    public function findArchivedArticlesInPeriod($start, $end)
    {
        $sql = <<<'SQL'
SELECT id, date, status, categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles
    WHERE status = :status AND date >= :start AND date < :end
    ORDER BY date DESC, id DESC
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':status', 2, SQLITE3_INTEGER);
        $statement->bindValue(':start', $start, SQLITE3_INTEGER);
        $statement->bindValue(':end', $end, SQLITE3_INTEGER);
        $result = $statement->execute();
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /**
     * @return int[]
     */
    public function findArchiveYears()
    {
        $db = $this->db->getConnection();
        $sql = <<<'SQL'
SELECT DISTINCT strftime('%Y', date, 'unixepoch') AS year
    FROM articles ORDER BY year
SQL;
        $res = $db->query($sql);
        $years = array();
        while (($record = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
            $years[] = (int) $record['year'];
        }
        return $years;
    }

    /**
     * @param string $search
     * @return Article[]
     */
    public function findArchivedArticlesContaining($search)
    {
        $sql = <<<'SQL'
SELECT id, date, status, categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles
    WHERE (title LIKE :text OR body LIKE :text) AND status = 2
    ORDER BY date DESC, id DESC
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':text', '%' . $search . '%', SQLITE3_TEXT);
        $result = $statement->execute();
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /**
     * @param array<int> $statuses
     * @param string $category
     * @param string|null $search
     * @return int
     */
    public function countArticlesWithStatus(array $statuses, $category = 'all', $search = null)
    {
        $db = $this->db->getConnection();
        if (empty($statuses)) {
            $whereClause = 'WHERE 1 = 1';
        } else {
            $whereClause = sprintf('WHERE status IN (%s)', implode(', ', $statuses));
        }
        $categoryClause = ($category !== 'all')
            ? 'AND categories LIKE :category'
            : '';
        $searchClause = isset($search)
            ? 'AND (title LIKE :search OR body LIKE :search)'
            : '';
        $sql = <<<SQL
SELECT COUNT(*) AS count FROM articles $whereClause $categoryClause $searchClause
SQL;
        $statement = $db->prepare($sql);
        $statement->bindValue(':category', "%,$category,%", SQLITE3_TEXT);
        $statement->bindValue(':search', "%$search%", SQLITE3_TEXT);
        $result = $statement->execute();
        $record = $result->fetchArray(SQLITE3_ASSOC);
        return $record['count'];
    }

    /**
     * @param array<int> $statuses
     * @param int $limit
     * @param int $offset
     * @return Article[]
     */
    public function findArticlesWithStatus(array $statuses, $limit, $offset)
    {
        if (empty($statuses)) {
            $whereClause = '';
        } else {
            $whereClause = sprintf('WHERE status IN (%s)', implode(', ', $statuses));
        }
        $sql = <<<SQL
SELECT id, date, status, trim(categories, ',') as categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset
SQL;
        $connection = $this->db->getConnection();
        $result = $connection->query($sql);
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /**
     * @param int $count
     * @return Article[]
     */
    public function findFeedableArticles($count)
    {
        $sql = <<<SQL
SELECT id, date, status, categories, title, teaser, length(body) AS hasBody, feedable, commentable
    FROM articles WHERE status = 1 AND feedable = :feedable ORDER BY date DESC, id DESC
    LIMIT $count
SQL;
        $connection = $this->db->getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue(':feedable', 1, SQLITE3_INTEGER);
        $result = $statement->execute();
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new Article(...$record);
        }
        return $objects;
    }

    /**
     * @param int $limit
     * @return MostPopularArticle[]
     */
    public function findMostPopularArticles($limit)
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
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $objects[] = new MostPopularArticle(...$record);
        }
        return $objects;
    }

    /**
     * @return array
     */
    public function findAllCategories()
    {
        $db = $this->db->getConnection();
        $statement = $db->prepare('SELECT DISTINCT categories FROM articles');
        $result = $statement->execute();
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

     /**
     * @param int $id
     * @return FullArticle|null
     */
    public function findById($id)
    {
        $db = $this->db->getConnection();
        $statement = $db->prepare('SELECT * FROM articles WHERE id = :id');
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();
        $record = $result->fetchArray(SQLITE3_NUM);
        if ($record !== false) {
            return new FullArticle(...$record);
        } else {
            return null;
        }
    }
}
