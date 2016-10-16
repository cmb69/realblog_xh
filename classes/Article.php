<?php

/**
 * The articles.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Realblog
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

namespace Realblog;

/**
 * The articles.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Article
{
    /**
     * The ID.
     *
     * @var int
     */
    protected $id;

    /**
     * The date.
     *
     * @var int
     */
    protected $date;

    /**
     * The publishing date.
     *
     * @var int
     */
    protected $publishingDate;

    /**
     * The archiving date.
     *
     * @var int
     */
    protected $archivingDate;

    /**
     * The status.
     *
     * @var int
     */
    protected $status;

    /**
     * The title.
     *
     * @var string
     */
    protected $title;

    /**
     * The teaser.
     *
     * @var string (X)HTML.
     */
    protected $teaser;

    /**
     * The body.
     *
     * @var string (X)HTML.
     */
    protected $body;

    /**
     * Whether the article should be included in the RSS feed.
     *
     * @var bool
     */
    protected $feedable;

    /**
     * Whether the article is commentable.
     *
     * @var bool
     */
    protected $commentable;

    /**
     * Makes an array of articles from an array of records.
     *
     * @param array $records An array of records.
     *
     * @return array
     */
    public static function makeArticlesFromRecords($records)
    {
        return array_map(array('Realblog\Article', 'makeFromRecord'), $records);
    }

    /**
     * Makes a new article from a record.
     *
     * @param array $record A record.
     *
     * @return Article
     */
    public static function makeFromRecord($record)
    {
        $article = new self();
        list(
            $article->id, $article->date, $article->publishingDate,
            $article->archivingDate, $article->status,  $article->title,
            $article->teaser, $article->body, $article->feedable,
            $article->commentable
        ) = $record;
        return $article;
    }

    /**
     * Finds and returns all articles with a certain status ordered by date and ID.
     *
     * @param int $status A status.
     * @param int $order  Order 1 (ascending) or -1 (descending).
     *
     * @return array<Article>
     */
    public static function findArticles($status, $order = -1)
    {
        $db = DB::getConnection();
        if ($order === -1) {
            $sql = 'SELECT * FROM articles WHERE status = :status ORDER BY date DESC, id DESC';
        } else {
            $sql = 'SELECT * FROM articles WHERE status = :status ORDER BY date, id';
        }
        $statement = $db->prepare($sql);
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $result = $statement->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $records[] = $record;
        }
        return Article::makeArticlesFromRecords($records);
    }

    /**
     * Finds all articles with one of the statuses.
     *
     * @param array $statuses An array of statuses.
     *
     * @return array<Article>
     */
    public static function findArticlesWithStatus($statuses)
    {
        $db = DB::getConnection();
        if (empty($statuses)) {
            $sql = 'SELECT * FROM articles ORDER BY id DESC';
        } else {
            $sql = sprintf('SELECT * FROM articles WHERE status in (%s) ORDER BY id DESC',
                           implode(', ', $statuses));
        }
        $result = $db->query($sql);
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $records[] = $record;
        }
        return Article::makeArticlesFromRecords($records);
    }

    /**
     * Selects all archived articles within a certain period.
     *
     * @param int $start A start timestamp.
     * @param int $end   An end timestamp.
     *
     * @return array<Article>
     */
    public static function findArchivedArticlesInPeriod($start, $end)
    {
        $db = DB::getConnection();
        $sql = <<<'EOS'
SELECT * FROM articles
    WHERE status = :status AND date >= :start AND date < :end
    ORDER BY date DESC, id DESC
EOS;
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':status', 2, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $start, SQLITE3_INTEGER);
        $stmt->bindValue(':end', $end, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $records[] = $record;
        }
        return Article::makeArticlesFromRecords($records);
    }

    /**
     * Finds and returns all feedable articles ordered by date and ID.
     *
     * @return array<Article>
     */
    public static function findFeedableArticles()
    {
        $db = DB::getConnection();
        $sql = 'SELECT * FROM articles WHERE feedable = :feedable ORDER BY date DESC, id DESC';
        $statement = $db->prepare($sql);
        $statement->bindValue(':feedable', 1, SQLITE3_INTEGER);
        $result = $statement->execute();
        $records = array();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $records[] = $record;
        }
        return Article::makeArticlesFromRecords($records);
    }

    /**
     * @param string $field  A field name.
     * @param int    $status A status.
     *
     * @return void
     */
    public static function autoChangeStatus($field, $status)
    {
        $db = DB::getConnection();
        $sql = "UPDATE articles SET status = :status WHERE status < :status AND $field <= :date";
        $statement = $db->prepare($sql);
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $statement->bindValue(':date', strtotime('midnight'), SQLITE3_INTEGER);
        $statement->execute();
        $records = array();
    }

    /**
     * Finds an article by ID.
     *
     * @param int $id An ID.
     *
     * @return Article
     */
    public static function findById($id)
    {
        $db = DB::getConnection();
        $statement = $db->prepare('SELECT * FROM articles WHERE id = :id');
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();
        $record = $result->fetchArray(SQLITE3_NUM);
        if ($record !== false) {
            return Article::makeFromRecord($record);
        } else {
            return null;
        }
    }

    /**
     * Returns the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the date.
     *
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Returns the publishing date.
     *
     * @return int
     */
    public function getPublishingDate()
    {
        return $this->publishingDate;
    }

    /**
     * Returns the archiving date.
     *
     * @return int
     */
    public function getArchivingDate()
    {
        return $this->archivingDate;
    }

    /**
     * Returns the status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns the teaser.
     *
     * @return string (X)HTML.
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * Returns the body.
     *
     * @return string (X)HTML.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns whether the article should be included in the RSS feed.
     *
     * @return bool
     */
    public function isFeedable()
    {
        return $this->feedable;
    }

    /**
     * Returns whether the article is commentable.
     *
     * @return bool
     */
    public function isCommentable()
    {
        return $this->commentable;
    }

    /**
     * Sets the ID.
     *
     * @param int $id An ID.
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets the date.
     *
     * @param int $timestamp A timestamp.
     *
     * @return void
     */
    public function setDate($timestamp)
    {
        $this->date = $timestamp;
    }

    /**
     * Sets the publishing date.
     *
     * @param int $timestamp A timestamp.
     *
     * @return void
     */
    public function setPublishingDate($timestamp)
    {
        $this->publishingDate = $timestamp;
    }

    /**
     * Sets the archiving date.
     *
     * @param int $timestamp A timestamp.
     *
     * @return void
     */
    public function setArchivingDate($timestamp)
    {
        $this->archivingDate = $timestamp;
    }

    /**
     * Sets the status.
     *
     * @param int $status A status.
     *
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Sets the title.
     *
     * @param string $title A title.
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets the teaser.
     *
     * @param string $teaser An (X)HTML teaser.
     *
     * @return void
     */
    public function setTeaser($teaser)
    {
        $this->teaser = $teaser;
    }

    /**
     * Sets the body.
     *
     * @param string $body An (X)HTML body.
     *
     * @return void
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Sets whether the article should be included in the RSS feed.
     *
     * @param bool $flag A flag.
     *
     * @return void
     */
    public function setFeedable($flag)
    {
        $this->feedable = (bool) $flag;
    }

    /**
     * Sets whether the article is commentable.
     *
     * @param bool $flag A flag.
     *
     * @return void
     */
    public function setCommentable($flag)
    {
        $this->commentable = (bool) $flag;
    }

    /**
     * Inserts the article into the database.
     *
     * @return void
     */
    public function insert()
    {
        $db = DB::getConnection();
        $sql = <<<'EOS'
INSERT INTO articles
    VALUES (
        :id, :date, :publishing_date, :archiving_date, :status, :title, :teaser,
        :body, :feedable, :commentable
    )
EOS;
        $statement = $db->prepare($sql);
        $statement->bindValue(':id', null, SQLITE3_NULL);
        $statement->bindValue(':date', $this->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $this->publishingDate, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $this->archivingDate, SQLITE3_INTEGER);
        $statement->bindValue(':status', $this->status, SQLITE3_INTEGER);
        $statement->bindValue(':title', $this->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $this->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $this->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $this->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $this->commentable, SQLITE3_INTEGER);
        $statement->execute();
    }

    /**
     * Updates the article in the database.
     *
     * @return void
     */
    public function update()
    {
        $db = DB::getConnection();
        $sql = <<<'EOS'
UPDATE articles
    SET date = :date, publishing_date = :publishing_date,
    	archiving_date = :archiving_date, status = :status, title = :title,
        teaser = :teaser, body = :body, feedable = :feedable,
        commentable = :commentable
    WHERE id = :id
EOS;
        $statement = $db->prepare($sql);
        $statement->bindValue(':id', $this->id, SQLITE3_INTEGER);
        $statement->bindValue(':date', $this->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $this->publishingDate, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $this->archivingDate, SQLITE3_INTEGER);
        $statement->bindValue(':status', $this->status, SQLITE3_INTEGER);
        $statement->bindValue(':title', $this->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $this->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $this->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $this->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $this->commentable, SQLITE3_INTEGER);
        $statement->execute();
    }

    /**
     * Deletes the article from the database.
     *
     * @return void
     */
    public function delete()
    {
        $db = DB::getConnection();
        $statement = $db->prepare('DELETE FROM articles WHERE id = :id');
        $statement->bindValue(':id', $this->id, SQLITE3_INTEGER);
        $statement->execute();
    }

    /**
     * Returns the article as record.
     *
     * @return array
     */
    public function asRecord()
    {
        return array(
            $this->id, $this->date, $this->publishingDate, $this->archivingDate,
            $this->status, false, $this->title, $this->teaser, $this->body,
            $this->feedable, $this->commentable
        );
    }
}

?>
