<?php

/**
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

class Finder
{
    /**
     * @param int $status
     * @param int $order
     * @return array<stdClass>
     */
    public static function findArticles($status, $limit, $offset = 0, $order = -1, $category = 'all', $search = null)
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
SELECT id, date, title, teaser, commentable, length(body) AS body_length
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
        return self::fetchAllAsObject($sql, $bindings);
    }

    /**
     * @param int $start
     * @param int $end
     * @return array<stdClass>
     */
    public static function findArchivedArticlesInPeriod($start, $end)
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
        return self::fetchAllAsObject($sql, $bindings);
    }

    public static function findArchiveYears()
    {
        $db = DB::getConnection();
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
    public static function findArchivedArticlesContaining($search)
    {
        $sql = <<<'EOS'
SELECT id, date, title FROM articles
    WHERE (title LIKE :text OR body LIKE :text) AND status = 2
    ORDER BY date DESC, id DESC
EOS;
        $bindings = array(
            array(':text', '%' . $search . '%', SQLITE3_TEXT)
        );
        return self::fetchAllAsObject($sql, $bindings);
    }

    /**
     * @param array<int> $statuses
     * @return int
     */
    public static function countArticlesWithStatus(array $statuses, $category = 'all', $search = null)
    {
        $db = DB::getConnection();
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
    public static function findArticlesWithStatus(array $statuses, $limit, $offset)
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
        return self::fetchAllAsObject($sql);
    }

    /**
     * @param int $count
     * @return array<stdClass>
     */
    public static function findFeedableArticles($count)
    {
        $sql = <<<SQL
SELECT id, date, title, teaser
    FROM articles WHERE status = 1 AND feedable = :feedable ORDER BY date DESC, id DESC
    LIMIT $count
SQL;
        $bindings = array(
            array(':feedable', 1, SQLITE3_INTEGER)
        );
        return self::fetchAllAsObject($sql, $bindings);
    }

    /**
     * @param int $limit
     * @return stdClass[]
     */
    public static function findMostPopularArticles($limit)
    {
        $sql = <<<SQL
SELECT articles.id, articles.title, COUNT(*) AS page_views
    FROM articles LEFT JOIN page_views ON articles.id = page_views.article_id
    GROUP BY articles.id
    ORDER BY page_views DESC
    LIMIT $limit
SQL;
        return self::fetchAllAsObject($sql);
    }

    /**
     * @param string $sql
     * @return stdClass[]
     */
    private static function fetchAllAsObject($sql, array $bindings = null)
    {
        $connection = DB::getConnection();
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
    public static function findAllCategories()
    {
        $db = DB::getConnection();
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
     * @return stdClass
     */
    public static function findById($id)
    {
        $db = DB::getConnection();
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
