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
     * @return array<stdClass>
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
        $sql = <<<EOS
SELECT id, date, title, teaser, categories, commentable, length(body) AS body_length
    FROM articles
    WHERE status = :status $categoryClause $searchClause
    ORDER BY date $order, id $order
    LIMIT $limit OFFSET $offset
EOS;
        $bindings = array(
            array(':status', $status, SQLITE3_INTEGER),
            array(':category', "%,$category,%", SQLITE3_TEXT),
            array(':search', "%$search%", SQLITE3_TEXT)
        );
        return $this->fetchAllAsObject($sql, $bindings);
    }

    /**
     * @param int $start
     * @param int $end
     * @return array<stdClass>
     */
    public function findArchivedArticlesInPeriod($start, $end)
    {
        $sql = <<<'EOS'
SELECT id, date, title FROM articles
    WHERE status = :status AND date >= :start AND date < :end
    ORDER BY date DESC, id DESC
EOS;
        $bindings = array(
            array(':status', 2, SQLITE3_INTEGER),
            array(':start', $start, SQLITE3_INTEGER),
            array(':end', $end, SQLITE3_INTEGER)
        );
        return $this->fetchAllAsObject($sql, $bindings);
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
     * @return array<stdClass>
     */
    public function findArchivedArticlesContaining($search)
    {
        $sql = <<<'EOS'
SELECT id, date, title FROM articles
    WHERE (title LIKE :text OR body LIKE :text) AND status = 2
    ORDER BY date DESC, id DESC
EOS;
        $bindings = array(
            array(':text', '%' . $search . '%', SQLITE3_TEXT)
        );
        return $this->fetchAllAsObject($sql, $bindings);
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
     * @return array<stdClass>
     */
    public function findArticlesWithStatus(array $statuses, $limit, $offset)
    {
        if (empty($statuses)) {
            $whereClause = '';
        } else {
            $whereClause = sprintf('WHERE status IN (%s)', implode(', ', $statuses));
        }
        $sql = <<<EOS
SELECT id, date, status, trim(categories, ',') as categories, title, feedable, commentable
    FROM articles $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset
EOS;
        return $this->fetchAllAsObject($sql);
    }

    /**
     * @param int $count
     * @return array<stdClass>
     */
    public function findFeedableArticles($count)
    {
        $sql = <<<SQL
SELECT id, date, title, teaser
    FROM articles WHERE status = 1 AND feedable = :feedable ORDER BY date DESC, id DESC
    LIMIT $count
SQL;
        $bindings = array(
            array(':feedable', 1, SQLITE3_INTEGER)
        );
        return $this->fetchAllAsObject($sql, $bindings);
    }

    /**
     * @param int $limit
     * @return stdClass[]
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
        return $this->fetchAllAsObject($sql);
    }

    /**
     * @param string $sql
     * @return stdClass[]
     */
    private function fetchAllAsObject($sql, array $bindings = null)
    {
        $connection = $this->db->getConnection();
        if (isset($bindings)) {
            $statement = $connection->prepare($sql);
            foreach ($bindings as $binding) {
                call_user_func_array(array($statement, 'bindValue'), $binding);
            }
            $result = $statement->execute();
        } else {
            $result = $connection->query($sql);
        }
        $objects = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $objects[] = (object) $record;
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
     * @return stdClass|null
     */
    public function findById($id)
    {
        $db = $this->db->getConnection();
        $statement = $db->prepare('SELECT * FROM articles WHERE id = :id');
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();
        $record = $result->fetchArray(SQLITE3_ASSOC);
        if ($record !== false) {
            return (object) $record;
        } else {
            return null;
        }
    }
}
