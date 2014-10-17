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
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The articles.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_Article
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
        return array_map(array('Realblog_Article', 'makeFromRecord'), $records);
    }

    /**
     * Makes a new article from a record.
     *
     * @param array $record A record.
     *
     * @return Realblog_Article
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
