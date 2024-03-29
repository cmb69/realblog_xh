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

use Realblog\Value\FullArticle;
use SQLite3;

class DB
{
    /** @var string */
    private $filename;

    /** @var SQLite3|null */
    private $connection = null;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function getConnection(): SQLite3
    {
        if ($this->connection === null) {
            $this->init();
            assert($this->connection !== null);
        }
        return $this->connection;
    }

    /** @return void */
    private function init()
    {
        try {
            $this->connection = new Sqlite3($this->filename, SQLITE3_OPEN_READWRITE);
            if ($this->filename === ":memory:") {
                $this->createDatabase();
            }
        } catch (\Exception $ex) {
            $dirname = dirname($this->filename);
            if (!file_exists($dirname)) {
                mkdir($dirname, 0777);
                chmod($dirname, 0777);
            }
            $this->connection = new Sqlite3($this->filename);
            $this->createDatabase();
        }
        $this->updateDatabase();
    }

    /** @return void */
    private function createDatabase()
    {
        $sql = <<<'EOS'
CREATE TABLE articles (
    id  INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    version INTEGER,
    date INTEGER,
    publishing_date INTEGER,
    archiving_date INTEGER,
    status INTEGER CHECK (status BETWEEN 0 AND 2),
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
        assert($this->connection !== null);
        $this->connection->exec($sql);
        $this->importFlatfile();
    }

    /** @return void */
    private function importFlatfile()
    {
        $filename = dirname($this->filename) . "/realblog.txt";
        if (is_readable($filename)) {
            $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            assert($lines !== false);
            assert($this->connection !== null);
            $this->connection->exec('BEGIN TRANSACTION');
            $sql = <<<'SQL'
INSERT INTO articles VALUES (
    :id, 1, :date, :publishing_date, :archiving_date, :status,
    :categories, :title, :teaser, :body, :feedable, :commentable
)
SQL;
            $statement = $this->connection->prepare($sql);
            assert($statement !== false);
            foreach ($lines as $line) {
                $record = explode("\t", $line);
                $status = ($record[4] == 1 || $record[4] == 2) ? $record[4] : 0;
                $categories = array_merge(
                    $this->getAndRemoveCategories($record[7]),
                    $this->getAndRemoveCategories($record[8])
                );
                $categories = implode(',', $categories);
                $statement->bindValue(':id', $record[0], SQLITE3_INTEGER);
                $statement->bindValue(':date', $record[1], SQLITE3_INTEGER);
                $statement->bindValue(':publishing_date', $record[2], SQLITE3_INTEGER);
                $statement->bindValue(':archiving_date', $record[3], SQLITE3_INTEGER);
                $statement->bindValue(':status', $status, SQLITE3_INTEGER);
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

    /** @return list<string> */
    private function getAndRemoveCategories(string &$field): array
    {
        if (!preg_match('/{{{(?:PLUGIN:)?rbCat\(([^\)]*)\);?}}}/', $field, $matches)) {
            return [];
        }
        $categories = explode('|', trim($matches[1], "'|"));
        $categories = array_map(
            function ($cat) {
                return trim($cat);
            },
            $categories
        );
        $field = preg_replace('/{{{(?:PLUGIN:)?rbCat\([^\)]*\);?}}}/', '', $field);
        return $categories;
    }

    /** @return void */
    private function updateDatabase()
    {
        $sql = <<<'EOS'
CREATE TABLE IF NOT EXISTS page_views (
    article_id INTEGER NOT NULL,
    timestamp INTEGER NOT NULL
);
EOS;
        assert($this->connection !== null);
        $this->connection->exec($sql);
    }

    public function insertArticle(FullArticle $article): int
    {
        $conn = $this->getConnection();
        $sql = <<<'EOS'
INSERT INTO articles
    VALUES (
        :id, 1, :date, :publishing_date, :archiving_date, :status,
        :categories, :title, :teaser, :body, :feedable, :commentable
    )
EOS;
        $statement = $conn->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':id', null, SQLITE3_NULL);
        $statement->bindValue(':date', $article->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $article->publishingDate, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $article->archivingDate, SQLITE3_INTEGER);
        $statement->bindValue(':status', $article->status, SQLITE3_INTEGER);
        $statement->bindValue(':categories', $article->categories, SQLITE3_TEXT);
        $statement->bindValue(':title', $article->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $article->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $article->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $article->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $article->commentable, SQLITE3_INTEGER);
        $res = $statement->execute();
        if ($res) {
            $res = $conn->changes();
        }
        return (int) $res;
    }

    public function updateArticle(FullArticle $article): int
    {
        $conn = $this->getConnection();
        $sql = <<<'EOS'
UPDATE articles
    SET version = version + 1, date = :date, publishing_date = :publishing_date,
        archiving_date = :archiving_date, status = :status,
        categories = :categories, title = :title, teaser = :teaser, body = :body,
        feedable = :feedable, commentable = :commentable
    WHERE id = :id AND version = :version
EOS;
        $statement = $conn->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':id', $article->id, SQLITE3_INTEGER);
        $statement->bindValue(':version', $article->version, SQLITE3_INTEGER);
        $statement->bindValue(':date', $article->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $article->publishingDate, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $article->archivingDate, SQLITE3_INTEGER);
        $statement->bindValue(':status', $article->status, SQLITE3_INTEGER);
        $statement->bindValue(':categories', $article->categories, SQLITE3_TEXT);
        $statement->bindValue(':title', $article->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $article->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $article->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $article->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $article->commentable, SQLITE3_INTEGER);
        $res = $statement->execute();
        if ($res) {
            $res = $conn->changes();
        }
        return (int) $res;
    }

    /** @return void */
    public function autoChangeStatus(string $field, int $status)
    {
        $conn = $this->getConnection();
        $sql = <<<SQL
UPDATE articles SET version = version + 1, status = :status
    WHERE status < :status AND $field <= :date
SQL;
        $statement = $conn->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $statement->bindValue(':date', strtotime('midnight'), SQLITE3_INTEGER);
        $statement->execute();
    }

    /** @param list<int> $ids */
    public function updateStatusOfArticlesWithIds(array $ids, int $status): int
    {
        $sql = sprintf(
            'UPDATE articles SET version = version + 1, status = :status WHERE id in (%s)',
            implode(',', $ids)
        );
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        assert($stmt !== false);
        $stmt->bindValue(':status', $status, SQLITE3_INTEGER);
        $res = $stmt->execute();
        if ($res) {
            $res = $conn->changes();
        }
        return (int) $res;
    }

    public function deleteArticle(FullArticle $article): int
    {
        $sql = 'DELETE FROM articles WHERE id = :id AND version = :version';
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        assert($stmt !== false);
        $stmt->bindValue(':id', $article->id, SQLITE3_INTEGER);
        $stmt->bindValue(':version', $article->version, SQLITE3_INTEGER);
        $res = $stmt->execute();
        if ($res) {
            $res = $conn->changes();
        }
        return (int) $res;
    }

    /** @param list<int> $ids */
    public function deleteArticlesWithIds(array $ids): int
    {
        $sql = sprintf(
            'DELETE FROM articles WHERE id in (%s)',
            implode(',', $ids)
        );
        $conn = $this->getConnection();
        $res = $conn->exec($sql);
        if ($res) {
            $res = $conn->changes();
        }
        return (int) $res;
    }

    /** @return void */
    public function recordPageView(int $articleId)
    {
        $sql = 'INSERT INTO page_views VALUES (:article_id, :timestamp)';
        $conn = $this->getConnection();
        $statement = $conn->prepare($sql);
        assert($statement !== false);
        $statement->bindValue(':article_id', $articleId, SQLITE3_INTEGER);
        $statement->bindValue(':timestamp', time());
        $statement->execute();
    }

    public function exportToCsv(string $filename): bool
    {
        if (!($stream = @fopen($filename, 'w'))) {
            return false;
        }
        $sql = <<<SQL
SELECT id, date, publishing_date, archiving_date, status, categories, title, teaser, body, feedable, commentable
FROM articles
SQL;
        $conn = $this->getConnection();
        $statement = $conn->prepare($sql);
        assert($statement !== false);
        $result = $statement->execute();
        assert($result !== false);
        $record = [];
        for ($i = 0; $i < $result->numColumns(); $i++) {
            $record[] = $result->columnName($i);
        }
        fputcsv($stream, $record, ",", "\"", "\0");
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $record[1] = date("Y-m-d H:i:s", $record[1]);
            $record[2] = date("Y-m-d H:i:s", $record[2]);
            $record[3] = date("Y-m-d H:i:s", $record[3]);
            $record[5] = trim($record[5], ",");
            fputcsv($stream, $record, ",", "\"", "\0");
        }
        fclose($stream);
        return true;
    }

    public function importFromCsv(string $filename): bool
    {
        $conn = $this->getConnection();
        $conn->exec('BEGIN TRANSACTION');
        $conn->exec('DELETE FROM articles');
        $sql = <<<'EOS'
INSERT INTO articles
    VALUES (
        :id, :version, :date, :publishing_date, :archiving_date, :status,
        :categories, :title, :teaser, :body, :feedable, :commentable
    )
EOS;
        $statement = $conn->prepare($sql);
        assert($statement !== false);
        if (!($stream = fopen($filename, 'r'))) {
            return false;
        }
        fgetcsv($stream, 0, ",", "\"", "\0");
        while (($record = fgetcsv($stream, 0, ",", "\"", "\0")) !== false) {
            assert($record !== null);
            $statement->bindValue(':id', $record[0], SQLITE3_INTEGER);
            $statement->bindValue(':version', 0, SQLITE3_INTEGER);
            $statement->bindValue(':date', strtotime((string) $record[1]), SQLITE3_INTEGER);
            $statement->bindValue(':publishing_date', strtotime((string) $record[2]), SQLITE3_INTEGER);
            $statement->bindValue(':archiving_date', strtotime((string) $record[3]), SQLITE3_INTEGER);
            $statement->bindValue(':status', $record[4], SQLITE3_INTEGER);
            $statement->bindValue(':categories', ",{$record[5]},", SQLITE3_TEXT);
            $statement->bindValue(':title', $record[6], SQLITE3_TEXT);
            $statement->bindValue(':teaser', $record[7], SQLITE3_TEXT);
            $statement->bindValue(':body', $record[8], SQLITE3_TEXT);
            $statement->bindValue(':feedable', $record[9], SQLITE3_INTEGER);
            $statement->bindValue(':commentable', $record[10], SQLITE3_INTEGER);
            if (!$statement->execute()) {
                return false;
            }
        }
        fclose($stream);
        $conn->exec('COMMIT');
        return true;
    }
}
