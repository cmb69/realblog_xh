<?php

/**
 * Testing Realblog_searchClause().
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
require_once './classes/fields.php';
require_once './functions.php';

/**
 * Testing Realblog_searchClause().
 *
 * @category Testing
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class SearchClauseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests searching for nothing.
     *
     * @return void
     */
    public function testSearchNothing()
    {
        $this->assertNull(Realblog_searchClause());
    }

    /**
     * Tests searching for title only.
     *
     * @return void
     */
    public function testSearchTitleOnly()
    {
        $_REQUEST = array(
            'realblog_title' => 'foo',
            'title_operator' => '2',
            'operator_2' => 'OR',
            'realblog_story' => '',
            'story_operator' => '2'
        );
        $this->assertInstanceOf('LikeWhereClause', Realblog_searchClause());
    }

    /**
     * Tests searching for story only.
     *
     * @return void
     */
    public function testSearchStoryOnly()
    {
        $_REQUEST = array(
            'realblog_title' => '',
            'title_operator' => '2',
            'operator_2' => 'OR',
            'realblog_story' => 'foo',
            'story_operator' => '2'
        );
        $this->assertInstanceOf('LikeWhereClause', Realblog_searchClause());
    }

    /**
     * Tests searching for title or story.
     *
     * @return void
     */
    public function testSearchTitleOrStory()
    {
        $_REQUEST = array(
            'realblog_title' => 'foo',
            'title_operator' => '2',
            'operator_2' => 'OR',
            'realblog_story' => 'bar',
            'story_operator' => '2'
        );
        $this->assertInstanceOf('OrWhereClause', Realblog_searchClause());
    }

    /**
     * Tests searching for title and story.
     *
     * @return void
     */
    public function testSearchTitleAndStory()
    {
        $_REQUEST = array(
            'realblog_title' => 'foo',
            'title_operator' => '2',
            'operator_2' => 'AND',
            'realblog_story' => 'bar',
            'story_operator' => '2'
        );
        $this->assertInstanceOf('AndWhereClause', Realblog_searchClause());
    }
}

?>
