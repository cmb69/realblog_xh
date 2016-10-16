<?php

/**
 * The DB.
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
 * The DB.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class DB
{
    /**
     * The unique instance.
     *
     * @var DB
     */
    protected static $instance;

    /**
     * The connection.
     *
     * @var \SQLite3
     */
    protected $connection;

    /**
     * Returns the connection.
     *
     * @return \SQLite3
     */
    public static function getConnection()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    /**
     * Initializes a new instance.
     *
     * @global array The paths of system files and folders.
     */
    protected function __construct()
    {
        global $pth;

        $filename = "{$pth['folder']['content']}realblog/realblog.db";
        try {
            $this->connection = new \Sqlite3($filename, SQLITE3_OPEN_READWRITE);
        } catch (\Exception $ex) {
            $this->connection = new \Sqlite3($filename);
            $this->createDatabase();
        }
    }

    private function createDatabase()
    {
        $sql = <<<'EOS'
CREATE TABLE articles (
	id	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	date INTEGER,
	publishing_date INTEGER,
	archiving_date INTEGER,
	status INTEGER,
	title TEXT,
	teaser TEXT,
	body TEXT,
	feedable INTEGER,
	commentable INTEGER
)
EOS;
        $this->connection->exec($sql);
        $this->importFlatfile();
    }

    private function importFlatfile()
    {
        global $pth;

        $types = array(SQLITE3_INTEGER, SQLITE3_INTEGER, SQLITE3_INTEGER,
                       SQLITE3_INTEGER, SQLITE3_INTEGER, SQLITE3_TEXT,
                       SQLITE3_TEXT, SQLITE3_TEXT, SQLITE3_INTEGER,
                       SQLITE3_INTEGER);
        $filename = "{$pth['folder']['content']}realblog/realblog.txt";
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->connection->exec("BEGIN TRANSACTION");
        $sql = "INSERT INTO articles VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $statement = $this->connection->prepare($sql);
        foreach ($lines as $line) {
            $record = explode("\t", $line);
            unset($record[5]);
            $record = array_values($record);
            foreach ($record as $i => $field) {
                $statement->bindValue($i + 1, $record[$i], $types[$i]);
            }
            $statement->execute();
        }
        $this->connection->exec("COMMIT TRANSACTION");
    }
}

?>
