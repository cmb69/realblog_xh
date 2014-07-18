<?php

/**
 * Testing the article views.
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

require_once '../../cmsimple/functions.php';
require_once './classes/Presentation.php';
require_once './classes/fields.php';

/**
 * A dummy.
 *
 * @return void
 */
function comments()
{
}

/**
 * Testing the article views.
 *
 * @category Testing
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class ArticleViewTest extends PHPUnit_Framework_TestCase
{
    /**
     * The test subject.
     *
     * @var Realblog_ArticleView
     */
    private $_subject;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The configuration of the plugins.
     * @global array  The localization of the plugins.
     * @global bool   Whether we're in admin mode.
     */
    public function setUp()
    {
        global $sn, $su, $plugin_cf, $plugin_tx, $adm;

        $sn = '/xh/';
        $su = 'Blog';
        $plugin_cf['realblog'] = array(
            'comments_function' => 'true'
        );
        $plugin_tx['realblog'] = array(
            'blog_back' => 'Overview',
            'comment_edit' => 'Edit comments',
            'display_date_format' => '%Y-%m-%d',
            'entry_edit' => 'Edit entry'
        );
        $adm = false;
        $article = array(
            '1', '1405548000', '1405548000', '1405548000', '1', '',
            'Heading', '<p>Teaser</p>', '<p>Article</p>', '', ''
        );
        $this->_subject = new Realblog_ArticleView('1', $article, '1');
    }

    /**
     * Tests that the container is rendered.
     *
     * @return void
     */
    public function testRendersContainer()
    {
        $this->_assertRenders(
            array(
                'tag' => 'div',
                'attributes' => array('class' => 'realblog_show_box')
            )
        );
    }

    /**
     * Tests that the overview link is rendered.
     *
     * @return void
     */
    public function testRendersOverviewLink()
    {
        $this->_assertRenders(
            array(
                'tag' => 'a',
                'attributes' => array(
                    'href' => '/xh/?Blog&page=1'
                ),
                'content' => 'Overview',
                'parent' => array(
                    'tag' => 'span',
                    'attributes' => array('class' => 'realblog_button'),
                    'parent' => array(
                        'tag' => 'div',
                        'attributes' => array('class' => 'realblog_buttons')
                    )
                )
            )
        );
    }

    /**
     * Tests that the edit entry link is rendered in admin mode.
     *
     * @return void
     *
     * @global bool Whether we're in admin mode.
     */
    public function testRendersEditEntryLinkInAdminMode()
    {
        global $adm;

        $adm = true;
        $this->_assertRenders(
            array(
                'tag' => 'a',
                'attributes' => array(
                    'href' => '/xh/?&realblog&admin=plugin_main'
                        . '&action=modify_realblog&realblogID=1'
                ),
                'content' => 'Edit entry'
            )
        );
    }

    /**
     * Tests that the edit comments link is rendered.
     *
     * @return void
     *
     * @global bool Whether we're in admin mode.
     */
    public function testRendersEditCommentsLink()
    {
        global $adm;

        $adm = true;
        $this->_assertRenders(
            array(
                'tag' => 'a',
                'attributes' => array(
                    'href' => '/xh/?&comments&admin=plugin_main'
                        . '&action=plugin_text&selected=comments1.txt'
                ),
                'content' => 'Edit comments'
            )
        );
    }

    /**
     * Tests that the heading is rendered.
     *
     * @return void
     */
    public function testRendersHeading()
    {
        $this->_assertRenders(
            array(
                'tag' => 'h4',
                'content' => 'Heading'
            )
        );
    }

    /**
     * Tests that the date is rendered.
     *
     * @return void
     */
    public function testRendersDate()
    {
        $this->_assertRenders(
            array(
                'tag' => 'div',
                'attributes' => array('class' => 'realblog_show_date'),
                //'content' => '2014-07-17'
            )
        );
    }

    /**
     * Tests that the story is rendered.
     *
     * @return void
     */
    public function testRendersStory()
    {
        $this->_assertRenders(
            array(
                'tag' => 'div',
                'attributes' => array('class' => 'realblog_show_story_entry'),
                'child' => array(
                    'tag' => 'p',
                    'content' => 'Article'
                )
            )
        );
    }

    /**
     * Asserts that a matcher is rendered.
     *
     * @param array $matcher A matcher.
     *
     * @return void
     */
    private function _assertRenders($matcher)
    {
        $this->assertTag($matcher, $this->_subject->render());
    }
}

?>
