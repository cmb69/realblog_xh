<?php

/**
 * The article views.
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
 * The article views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticleView
{
    /**
     * The article ID.
     *
     * @var int
     */
    protected $id;

    /**
     * The article record.
     *
     * @var Realblog_Article
     */
    protected $article;

    /**
     * The article page. Most likely this is always 1.
     *
     * @var int
     */
    protected $page;

    /**
     * Initializes a new instance.
     *
     * @param int              $id      An article ID.
     * @param Realblog_Article $article An article record.
     * @param int              $page    An article page.
     *
     * @return void
     */
    public function __construct($id, Realblog_Article $article, $page)
    {
        $this->id = (int) $id;
        $this->article = $article;
        $this->page = (int) $page;
    }

    /**
     * Renders the article.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     */
    public function render()
    {
        global $plugin_cf;

        $html = '<div class="realblog_show_box">'
            . $this->renderLinks() . $this->renderHeading()
            . $this->renderDate() . $this->renderStory()
            . $this->renderLinks() . '</div>';
        // output comments in RealBlog
        if ($this->wantsComments() && $this->article->isCommentable()) {
            $realblog_comments_id = 'comments' . $this->id;
            $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
            $html .= call_user_func(array($bridge, handle), $realblog_comments_id);
        }
        return $html;
    }

    /**
     * Renders the links.
     *
     * @return string (X)HTML.
     */
    protected function renderLinks()
    {
        $html = '<div class="realblog_buttons">'
            . $this->renderOverviewLink();
        if (XH_ADM) {
            if ($this->wantsComments()) {
                $html .= $this->renderEditCommentsLink();
            }
            $html .= $this->renderEditEntryLink();
        }
        $html .= '<div style="clear: both;"></div>'
            . '</div>';
        return $html;
    }

    /**
     * Renders the overview link.
     *
     * @return string (X)HTML.
     *
     * @global string              The URL of the current page.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderOverviewLink()
    {
        global $su, $plugin_tx, $_Realblog_controller;

        if ($this->article->getStatus() == 2) {
            $url = $_Realblog_controller->url(
                $su, null, array('realblog_year' => $_Realblog_controller->getYear())
            );
            $text = $plugin_tx['realblog']['archiv_back'];
        } else {
            $url = $_Realblog_controller->url(
                $su, null, array('realblog_page' => $this->page)
            );
            $text = $plugin_tx['realblog']['blog_back'];
        }
        return '<span class="realblog_button">'
            . '<a href="' . XH_hsc($url) . '">' . $text . '</a></span>';
    }

    /**
     * Renders the edit entry link.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderEditEntryLink()
    {
        global $sn, $plugin_tx;

        return '<span class="realblog_button">'
            . '<a href="' . $sn . '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=modify_realblog&amp;realblogID='
            . $this->id . '">'
            . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
    }

    /**
     * Renders the edit comments link.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The configuration of the plugins.
     * @global array  The localization of the plugins.
     */
    protected function renderEditCommentsLink()
    {
        global $sn, $plugin_cf, $plugin_tx;

        $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
        $url = call_user_func(array($bridge, getEditUrl), 'realblog' . $this->id);
        if ($url) {
            return '<span class="realblog_button"><a href="' . XH_hsc($url) . '">'
                . $plugin_tx['realblog']['comment_edit'] . '</a></span>';
        } else {
            return '';
        }
    }

    /**
     * Renders the article heading.
     *
     * @return string (X)HTML.
     *
     * @todo Heed $cf[menu][levels].
     */
    protected function renderHeading()
    {
        return '<h4>' . $this->article->getTitle() . '</h4>';
    }

    /**
     * Renders the article date.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderDate()
    {
        global $plugin_tx;

        $date = date(
            $plugin_tx['realblog']['date_format'],
            $this->article->getDate()
        );
        return '<div class="realblog_show_date">' . $date . '</div>';
    }

    /**
     * Renders the article story.
     *
     * @return string (X)HTML.
     */
    protected function renderStory()
    {
        $story = $this->article->getBody() != ''
            ? $this->article->getBody()
            : $this->article->getTeaser();
        return '<div class="realblog_show_story_entry">'
            . evaluate_scripting($story)
            . '</div>';
    }

    /**
     * Returns whether comments are enabled.
     *
     * @return bool
     *
     * @global array The configuration of the plugins.
     */
    protected function wantsComments()
    {
        global $plugin_cf;

        $pcf = $plugin_cf['realblog'];
        return $pcf['comments_plugin']
            && class_exists($pcf['comments_plugin'] . '_RealblogBridge');
    }
}

?>
