<?php

/**
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

use stdClass;

class ArticleView
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var stdClass
     */
    protected $article;

    /**
     * @var int
     */
    protected $page;

    /**
     * @param int $id
     * @param int $page
     */
    public function __construct($id, stdClass $article, $page)
    {
        $this->id = (int) $id;
        $this->article = $article;
        $this->page = (int) $page;
    }

    /**
     * @return string
     * @global array $plugin_cf
     */
    public function render()
    {
        global $plugin_cf;

        $html = '<div class="realblog_show_box">'
            . $this->renderLinks() . $this->renderHeading()
            . $this->renderDate() . $this->renderStory()
            . $this->renderLinks() . '</div>';
        // output comments in RealBlog
        if ($this->wantsComments() && $this->article->commentable) {
            $realblog_comments_id = 'comments' . $this->id;
            $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
            $html .= call_user_func(array($bridge, handle), $realblog_comments_id);
        }
        return $html;
    }

    /**
     * @return string
     */
    private function renderLinks()
    {
        $html = '<div class="realblog_buttons">'
            . $this->renderOverviewLink();
        if (defined('XH_ADM') && XH_ADM) {
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
     * @return string
     * @global string $su
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderOverviewLink()
    {
        global $su, $plugin_tx, $_Realblog_controller;

        if ($this->article->status == 2) {
            $url = $_Realblog_controller->url(
                $su,
                null,
                array('realblog_year' => $_Realblog_controller->getYear())
            );
            $text = $plugin_tx['realblog']['archiv_back'];
        } else {
            $url = $_Realblog_controller->url(
                $su,
                null,
                array('realblog_page' => $this->page)
            );
            $text = $plugin_tx['realblog']['blog_back'];
        }
        return '<span class="realblog_button">'
            . '<a href="' . XH_hsc($url) . '">' . $text . '</a></span>';
    }

    /**
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     */
    private function renderEditEntryLink()
    {
        global $sn, $plugin_tx;

        return '<span class="realblog_button">'
            . '<a href="' . $sn . '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=modify_realblog&amp;realblogID='
            . $this->id . '">'
            . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
    }

    /**
     * @return string
     * @global array $plugin_cf
     * @global array $plugin_tx
     */
    private function renderEditCommentsLink()
    {
        global $plugin_cf, $plugin_tx;

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
     * @return string
     * @todo Heed $cf[menu][levels].
     */
    private function renderHeading()
    {
        return '<h4>' . $this->article->title . '</h4>';
    }

    /**
     * @return string
     * @global array $plugin_tx
     */
    private function renderDate()
    {
        global $plugin_tx;

        $date = date(
            $plugin_tx['realblog']['date_format'],
            $this->article->date
        );
        return '<div class="realblog_show_date">' . $date . '</div>';
    }

    /**
     * @return string
     */
    private function renderStory()
    {
        $story = $this->article->body != ''
            ? $this->article->body
            : $this->article->teaser;
        return '<div class="realblog_show_story_entry">'
            . evaluate_scripting($story)
            . '</div>';
    }

    /**
     * @return bool
     * @global array $plugin_cf
     */
    private function wantsComments()
    {
        global $plugin_cf;

        $pcf = $plugin_cf['realblog'];
        return $pcf['comments_plugin']
            && class_exists($pcf['comments_plugin'] . '_RealblogBridge');
    }
}
