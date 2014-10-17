<?php

/**
 * Testing the functions.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Realblog
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

require_once './classes/flatfile.php';
require_once './classes/Controller.php';

/**
 * Testing the functions.
 *
 * @category Testing
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class FunctionsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the test fixtures.
     *
     * @return void
     *
     * @global Realblog_Controller The plugin controller.
     */
    public function setUp()
    {
        global $_Realblog_controller;

        $_Realblog_controller = new Realblog_Controller();
    }

    /**
     * Tests that the search clause is null.
     *
     * @return void
     *
     * @global Realblog_Controller The plugin controller.
     */
    public function testSearchClauseIsNull()
    {
        global $_Realblog_controller;

        $this->assertNull($_Realblog_controller->searchClause());
    }

    /**
     * Tests that the search clause is an instance of a certain class.
     *
     * @param string $title     A title.
     * @param string $operator  An operator ('OR' or 'AND').
     * @param string $story     A story.
     * @param string $className A class name.
     *
     * @return void
     *
     * @global Realblog_Controller The plugin controller.
     *
     * @dataProvider dataForSearchClauseIsA
     */
    public function testSearchClauseIsA($title, $operator, $story, $className)
    {
        global $_Realblog_controller;

        $_GET = array(
            'realblog_title' => $title,
            'title_operator' => '2',
            'realblog_search' => $operator,
            'realblog_story' => $story,
            'story_operator' => '2'
        );
        $this->assertInstanceOf($className, $_Realblog_controller->searchClause());
    }

    /**
     * Returns test data for testSearchClauseIsA().
     *
     * @return array
     */
    public function dataForSearchClauseIsA()
    {
        return array(
            array('foo', 'OR', '', 'LikeWhereClause'),
            array('', 'OR', 'foo', 'LikeWhereClause'),
            array('foo', 'OR', 'bar', 'OrWhereClause'),
            array('foo', 'AND', 'bar', 'AndWhereClause')
        );
    }

    /**
     * Tests stringToTime().
     *
     * @param string $format    A date format.
     * @param string $date      A date string.
     * @param int    $timestamp A timestamp.
     *
     * @return void
     *
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     *
     * @dataProvider dataForStringToTime
     */
    public function testStringToTime($format, $date, $timestamp)
    {
        global $plugin_tx, $_Realblog_controller;

        $this->assertEquals($timestamp, $_Realblog_controller->stringToTime($date));
    }

    /**
     * Returns test data for testStringToTime().
     *
     * @return array
     */
    public function dataForStringToTime()
    {
        return array(
            array('Y-m-d', '2014-07-25', 1406239200)
        );
    }
}

?>
