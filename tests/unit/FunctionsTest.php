<?php

/**
 * Testing the functions.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Realblog
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

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
     * @global Realblog\Controller The plugin controller.
     */
    public function setUp()
    {
        global $_Realblog_controller;

        $_Realblog_controller = new Realblog\Controller();
    }

    /**
     * Tests stringToTime().
     *
     * @param string $date      A date string.
     * @param int    $timestamp A timestamp.
     *
     * @return void
     *
     * @global Realblog\Controller The plugin controller.
     *
     * @dataProvider dataForStringToTime
     */
    public function testStringToTime($date, $timestamp)
    {
        global $_Realblog_controller;

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
            array('2014-07-25', 1406239200)
        );
    }
}

?>
