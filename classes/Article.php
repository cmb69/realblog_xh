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
            $article->archivingDate, $article->status, $_, $article->title,
            $article->teaser, $article->body, $article->feedable,
            $article->commentable
        ) = $record;
        return $article;
    }

    /**
     * Finds and returns all articles with a certain status ordered by date and ID.
     *
     * @param int $status A status.
     * @param int $order  Order ASCENDING or DESCENDING.
     *
     * @return array<Article>
     */
    public static function findArticles($status, $order = DESCENDING)
    {
        $db = DB::getConnection();
        return Article::makeArticlesFromRecords(
            $db->selectWhere(
                'realblog.txt',
                new \SimpleWhereClause(
                    REALBLOG_STATUS, '=', $status, INTEGER_COMPARISON
                ),
                -1,
                array(
                    new \OrderBy(
                        REALBLOG_DATE, $order, INTEGER_COMPARISON
                    ),
                    new \OrderBy(REALBLOG_ID, $order, INTEGER_COMPARISON)
                )
            )
        );
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
        $filterClause = null;
        foreach ($statuses as $i) {
            if (isset($filterClause)) {
                $filterClause = new \OrWhereClause(
                    $filterClause,
                    new \SimpleWhereClause(REALBLOG_STATUS, "=", $i)
                );
            } else {
                $filterClause = new \SimpleWhereClause(
                    REALBLOG_STATUS, "=", $i
                );
            }
        }
        return Article::makeArticlesFromRecords(
            $db->selectWhere(
                'realblog.txt', $filterClause, -1,
                new \OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
            )
        );
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
        return Article::makeArticlesFromRecords(
            $db->selectWhere(
                'realblog.txt',
                new \AndWhereClause(
                    new \SimpleWhereClause(
                        REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON
                    ),
                    new \SimpleWhereClause(
                        REALBLOG_DATE, '>=', $start, INTEGER_COMPARISON
                    ),
                    new \SimpleWhereClause(
                        REALBLOG_DATE, '<', $end, INTEGER_COMPARISON
                    )
                ),
                -1,
                array(
                    new \OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                    new \OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                )
            )
        );
    }

    /**
     * Finds and returns all feedable articles ordered by date and ID.
     *
     * @return array<Article>
     */
    public static function findFeedableArticles()
    {
        $db = DB::getConnection();
        return Article::makeArticlesFromRecords(
            $db->selectWhere(
                'realblog.txt',
                new \SimpleWhereClause(
                    REALBLOG_RSSFEED, "=", "on", STRING_COMPARISON
                ),
                -1,
                array(
                    new \OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
                    new \OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)
                )
            )
        );
    }

    /**
     * Finds all articles relevant for automatic status change.
     *
     * @param string $field  A field.
     * @param int    $status A status.
     *
     * @return array<Realblog_Articles>
     */
    public static function findArticlesForAutoStatusChange($field, $status)
    {
        $db = DB::getConnection();
        return Article::makeArticlesFromRecords(
            $db->selectWhere(
                'realblog.txt',
                new \AndWhereClause(
                    new \SimpleWhereClause(
                        REALBLOG_STATUS, '<', $status, INTEGER_COMPARISON
                    ),
                    new \SimpleWhereClause(
                        $field, '<=', strtotime('midnight'), INTEGER_COMPARISON
                    )
                )
            )
        );
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
        $record = $db->selectUnique('realblog.txt', REALBLOG_ID, $id);
        if (!empty($record)) {
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
        $this->feedable = $flag;
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
        $this->commentable = $flag;
    }

    /**
     * Inserts the article into the database.
     *
     * @return void
     */
    public function insert()
    {
        $db = DB::getConnection();
        $db->insertWithAutoId('realblog.txt', REALBLOG_ID, $this->asRecord());
    }

    /**
     * Updates the article in the database.
     *
     * @return void
     */
    public function update()
    {
        $db = DB::getConnection();
        $db->updateRowById('realblog.txt', REALBLOG_ID, $this->asRecord());
    }

    /**
     * Deletes the article from the database.
     *
     * @return void
     */
    public function delete()
    {
        $db = DB::getConnection();
        $db->deleteWhere(
            'realblog.txt', new \SimpleWhereClause(REALBLOG_ID, '=', $this->id),
            INTEGER_COMPARISON
        );
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
