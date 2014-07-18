<?php

/**
 * Testing the info views.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Realblog
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2012-2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

require_once './vendor/autoload.php';
require_once '../../cmsimple/functions.php';
require_once './classes/Presentation.php';

/**
 * Testing the info view.
 *
 * @category Testing
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class InfoViewTest extends PHPUnit_Framework_TestCase
{
    /**
     * The subject under test.
     *
     * @var Realblog_InfoView
     */
    private $_subject;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     */
    public function setUp()
    {
        global $pth, $plugin_tx;

        $this->_defineConstant('REALBLOG_VERSION', '1.0');
        $pth['folder']['plugins'] = './plugins/';
        $plugin_tx['realblog']['alt_logo'] = 'Ring file with pencil';
        $this->_subject = new Realblog_InfoView();
    }

    /**
     * Tests that the heading is rendered.
     *
     * @return void
     */
    public function testRendersHeading()
    {
        $this->assertTag(
            array(
                'tag' => 'h1',
                'content' => 'Realblog'
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that the plugin logo is rendered.
     *
     * @return void
     */
    public function testRendersLogo()
    {
        $this->assertTag(
            array(
                'tag' => 'img',
                'attributes' => array(
                    'src' => './plugins/realblog/realblog.png',
                    'class' => 'realblog_logo',
                    'alt' => 'Ring file with pencil'
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that the version info is rendered.
     *
     * @return void
     */
    public function testRendersVersion()
    {
        $this->assertTag(
            array(
                'tag' => 'p',
                'content' => 'Version: ' . REALBLOG_VERSION
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that the copyright info is rendered.
     *
     * @return void
     */
    public function testRendersCopyright()
    {
        $this->assertTag(
            array(
                'tag' => 'p',
                'content' => "Copyright \xC2\xA9 2014",
                'child' => array(
                    'tag' => 'a',
                    'attributes' => array(
                        'href' => 'http://3-magi.net/',
                        'target' => '_blank'
                    ),
                    'content' => 'Christoph M. Becker'
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that the license info is rendered.
     *
     * @return void
     */
    public function testRendersLicense()
    {
        $this->assertTag(
            array(
                'tag' => 'p',
                'attributes' => array('class' => 'realblog_license'),
                'content' => 'This program is free software:'
            ),
            $this->_subject->render()
        );
    }

    /**
     * (Re)defines a constant.
     *
     * @param string $name  A name.
     * @param string $value A value.
     *
     * @return void
     */
    private function _defineConstant($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        } else {
            runkit_constant_redefine($name, $value);
        }
    }
}

?>
