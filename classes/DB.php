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
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The DB.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_DB
{
    /**
     * The unique instance.
     *
     * @var Realblog_DB
     */
    protected static $instance;

    /**
     * The connection.
     *
     * @var flatfile
     */
    protected $connection;

    /**
     * Returns the connection.
     *
     * @return flatfile
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

        $this->connection = new Flatfile();
        $this->connection->datadir = $pth['folder']['content'] . 'realblog/';
    }
}

?>
