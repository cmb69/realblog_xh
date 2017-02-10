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
        $db = DB::getConnection();
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
        $statement = $db->prepare($sql);
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $statement->bindValue(':category', "%,$category,%", SQLITE3_TEXT);
        $statement->bindValue(':search', "%$search%", SQLITE3_TEXT);
        $result = $statement->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $records[] = (object) $record;
        }
        return $records;
    }

    /**
     * @param int $start
     * @param int $end
     * @return array<stdClass>
     */
    public static function findArchivedArticlesInPeriod($start, $end)
    {
        $db = DB::getConnection();
        $sql = <<<'EOS'
SELECT id, date, title FROM articles
    WHERE status = :status AND date >= :start AND date < :end
    ORDER BY date DESC, id DESC
EOS;
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':status', 2, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $start, SQLITE3_INTEGER);
        $stmt->bindValue(':end', $end, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $records[] = (object) $record;
        }
        return $records;
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
        $db = DB::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':text', '%' . $search . '%', SQLITE3_TEXT);
        $result = $stmt->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $records[] = (object) $record;
        }
        return $records;
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
        $db = DB::getConnection();
        if (empty($statuses)) {
            $whereClause = '';
        } else {
            $whereClause = sprintf('WHERE status IN (%s)', implode(', ', $statuses));
        }
        $sql = <<<EOS
SELECT id, date, status, title, feedable, commentable
    FROM articles $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset
EOS;
        $result = $db->query($sql);
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $records[] = (object) $record;
        }
        return $records;
    }

    /**
     * @param int $count
     * @return array<stdClass>
     */
    public static function findFeedableArticles($count)
    {
        $db = DB::getConnection();
        $sql = <<<SQL
SELECT id, date, title, teaser
    FROM articles WHERE status = 1 AND feedable = :feedable ORDER BY date DESC, id DESC
    LIMIT $count
SQL;
        $statement = $db->prepare($sql);
        $statement->bindValue(':feedable', 1, SQLITE3_INTEGER);
        $result = $statement->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $records[] = (object) $record;
        }
        return $records;
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
        $db = DB::getConnection();
        $statement = $db->prepare($sql);
        $result = $statement->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $records[] = (object) $record;
        }
        return $records;
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
