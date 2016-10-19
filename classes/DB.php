<?php

/**
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;
use SQLite3;

class DB
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var SQLite3
     */
    private $connection;

    /**
     * @return SQLite3
     */
    public static function getConnection()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    /**
     * @global array $pth
     */
    private function __construct()
    {
        global $pth;

        $filename = "{$pth['folder']['content']}realblog/realblog.db";
        try {
            $this->connection = new Sqlite3($filename, SQLITE3_OPEN_READWRITE);
        } catch (\Exception $ex) {
            $dirname = dirname($filename);
            if (!file_exists($dirname)) {
                mkdir($dirname, 0777);
                chmod($dirname, 0777);
            }
            $this->connection = new Sqlite3($filename);
            $this->createDatabase();
        }
    }

    /**
     * @return void
     */
    private function createDatabase()
    {
        $sql = <<<'EOS'
CREATE TABLE articles (
    id  INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    date INTEGER,
    publishing_date INTEGER,
    archiving_date INTEGER,
    status INTEGER,
    categories TEXT,
    title TEXT,
    teaser TEXT,
    body TEXT,
    feedable INTEGER,
    commentable INTEGER
);
CREATE INDEX status ON articles (status, date, id);
CREATE INDEX feedable ON articles (feedable, date, id);
EOS;
        $this->connection->exec($sql);
        $this->importFlatfile();
    }

    /**
     * @return void
     * @global array $pth
     */
    private function importFlatfile()
    {
        global $pth;

        $filename = "{$pth['folder']['content']}realblog/realblog.txt";
        if (file_exists($filename)) {
            $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $this->connection->exec('BEGIN TRANSACTION');
            $sql = <<<'SQL'
INSERT INTO articles VALUES (
    :id, :date, :publishing_date, :archiving_date, :status, :categories, :title,
    :teaser, :body, :feedable, :commentable
)
SQL;
            $statement = $this->connection->prepare($sql);
            foreach ($lines as $line) {
                $record = explode("\t", $line);
                $categories = array_merge(
                    $this->getAndRemoveCategories($record[7]),
                    $this->getAndRemoveCategories($record[8])
                );
                $categories = implode(',', $categories);
                $statement->bindValue(':id', $record[0], SQLITE3_INTEGER);
                $statement->bindValue(':date', $record[1], SQLITE3_INTEGER);
                $statement->bindValue(':publishing_date', $record[2], SQLITE3_INTEGER);
                $statement->bindValue(':archiving_date', $record[3], SQLITE3_INTEGER);
                $statement->bindValue(':status', $record[4], SQLITE3_INTEGER);
                $statement->bindValue(':categories', ",$categories,", SQLITE3_TEXT);
                $statement->bindValue(':title', $record[6], SQLITE3_TEXT);
                $statement->bindValue(':teaser', $record[7], SQLITE3_TEXT);
                $statement->bindValue(':body', $record[8], SQLITE3_TEXT);
                $statement->bindValue(':feedable', $record[9], SQLITE3_INTEGER);
                $statement->bindValue(':commentable', $record[10], SQLITE3_INTEGER);
                $statement->execute();
            }
            $this->connection->exec('COMMIT');
        }
    }

    /**
     * @param int &$field
     * @return array
     */
    private function getAndRemoveCategories(&$field)
    {
        $categories = preg_match('/{{{rbCat\(([^\)]*)\);?}}}/', $field, $matches);
        $categories = explode('|', trim($matches[1], "'|"));
        $categories = array_map(
            function ($cat) {
                return trim($cat);
            },
            $categories
        );
        $field = preg_replace('/{{{rbCat\([^\)]*\);?}}}/', '', $field);
        return $categories;
    }

    /**
     * @param int $status
     * @param int $order
     * @return array<stdClass>
     */
    public static function findArticles($status, $limit, $offset = 0, $order = -1, $category = 'all', $search = null)
    {
        $db = self::getConnection();
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
     * @return int
     */
    public static function countArchivedArticlesInPeriod($start, $end)
    {
        $db = self::getConnection();
        $sql = <<<'EOS'
SELECT COUNT(*) AS count FROM articles
    WHERE status = :status AND date >= :start AND date < :end
    ORDER BY date DESC, id DESC
EOS;
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':status', 2, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $start, SQLITE3_INTEGER);
        $stmt->bindValue(':end', $end, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $record = $result->fetchArray(SQLITE3_ASSOC);
        return $record['count'];
    }

    /**
     * @param int $start
     * @param int $end
     * @return array<stdClass>
     */
    public static function findArchivedArticlesInPeriod($start, $end)
    {
        $db = self::getConnection();
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
        $db = self::getConnection();
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
        $db = self::getConnection();
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
        $db = self::getConnection();
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
        $db = self::getConnection();
        $sql = <<<SQL
SELECT id, date, title, teaser
    FROM articles WHERE feedable = :feedable ORDER BY date DESC, id DESC
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
     * @param int $id
     * @return stdClass
     */
    public static function findById($id)
    {
        $db = self::getConnection();
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

    /**
     * @return void
     */
    public static function insertArticle(stdClass $article)
    {
        $db = self::getConnection();
        $sql = <<<'EOS'
INSERT INTO articles
    VALUES (
        :id, :date, :publishing_date, :archiving_date, :status, :categories,
        :title, :teaser, :body, :feedable, :commentable
    )
EOS;
        $statement = $db->prepare($sql);
        $statement->bindValue(':id', null, SQLITE3_NULL);
        $statement->bindValue(':date', $article->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $article->publishing_date, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $article->archiving_date, SQLITE3_INTEGER);
        $statement->bindValue(':status', $article->status, SQLITE3_INTEGER);
        $statement->bindValue(':categories', $article->categories, SQLITE3_TEXT);
        $statement->bindValue(':title', $article->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $article->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $article->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $article->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $article->commentable, SQLITE3_INTEGER);
        $statement->execute();
    }

    /**
     * @return void
     */
    public static function updateArticle(stdClass $article)
    {
        $db = self::getConnection();
        $sql = <<<'EOS'
UPDATE articles
    SET date = :date, publishing_date = :publishing_date,
        archiving_date = :archiving_date, status = :status,
        categories = :categories, title = :title, teaser = :teaser, body = :body,
        feedable = :feedable, commentable = :commentable
    WHERE id = :id
EOS;
        $statement = $db->prepare($sql);
        $statement->bindValue(':id', $article->id, SQLITE3_INTEGER);
        $statement->bindValue(':date', $article->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $article->publishing_date, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $article->archiving_date, SQLITE3_INTEGER);
        $statement->bindValue(':status', $article->status, SQLITE3_INTEGER);
        $statement->bindValue(':categories', $article->categories, SQLITE3_TEXT);
        $statement->bindValue(':title', $article->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $article->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $article->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $article->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $article->commentable, SQLITE3_INTEGER);
        $statement->execute();
    }

    /**
     * @param string $field
     * @param int $status
     * @return void
     */
    public static function autoChangeStatus($field, $status)
    {
        $db = self::getConnection();
        $sql = "UPDATE articles SET status = :status WHERE status < :status AND $field <= :date";
        $statement = $db->prepare($sql);
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $statement->bindValue(':date', strtotime('midnight'), SQLITE3_INTEGER);
        $statement->execute();
    }

    /**
     * @param array<int> $ids
     * @param int $status
     * @return void
     */
    public static function updateStatusOfArticlesWithIds(array $ids, $status)
    {
        $sql = sprintf(
            'UPDATE articles SET status = :status WHERE id in (%s)',
            implode(',', $ids)
        );
        $db = self::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':status', $status, SQLITE3_INTEGER);
        $stmt->execute();
    }

    /**
     * @param int id
     * @return void
     */
    public static function deleteArticleWithId($id)
    {
        $sql = 'DELETE FROM articles WHERE id = :id';
        $db = self::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
    }

    /**
     * @param array<int> $ids
     * @return void
     */
    public static function deleteArticlesWithIds(array $ids)
    {
        $sql = sprintf(
            'DELETE FROM articles WHERE id in (%s)',
            implode(',', $ids)
        );
        $db = self::getConnection();
        $db->exec($sql);
    }
}
