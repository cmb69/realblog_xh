<?php

/**
 * The articles views.
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

use stdClass;

/**
 * The articles views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class ArticlesView
{
    /**
     * The articles.
     *
     * @var array
     */
    protected $articles;
    
    protected $articleCount;
    
    protected $page;
    
    protected $pageCount;

    /**
     * Initializes a new instance.
     *
     * @param array  $articles        An array of articles.
     *
     * @return void
     */
    public function __construct($articles, $articleCount, $page, $pageCount)
    {
        $this->articles = $articles;
        $this->articleCount = $articleCount;
        $this->page = $page;
        $this->pageCount = $pageCount;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        global $_Realblog_controller, $su, $plugin_cf;

        $t = "\n" . '<div class="realblog_show_box">' . "\n";
        $url = $_Realblog_controller->url($su, null, array('realblog_page' => '%s'));
        $pagination = new PaginationView($this->articleCount, $this->page,
                                         $this->pageCount, $url);
        if ($plugin_cf['realblog']['pagination_top']) {
            $t .= $pagination->render();
        }
        $t .= "\n" . '<div style="clear:both;"></div>';
        $t .= $this->renderArticlePreviews();
        if ($plugin_cf['realblog']['pagination_bottom']) {
            $t .= $pagination->render();
        }
        $t .= '<div style="clear: both"></div></div>';
        return $t;
    }

    /**
     * Renders the article previews.
     *
     * @return string (X)HTML.
     */
    protected function renderArticlePreviews()
    {
        $t = '<div id="realblog_entries_preview" class="realblog_entries_preview">';
        foreach ($this->articles as $article) {
            $t .= $this->renderArticlePreview($article);
        }
        $t .= '<div style="clear: both;"></div>' . '</div>';
        return $t;
    }

    /**
     * Renders an article preview.
     *
     * @param stdClass $article An article.
     *
     * @return string (X)HTML.
     *
     * @global array      The configuration of the plugins.
     * @global Controller The plugin controller.
     */
    protected function renderArticlePreview(stdClass $article)
    {
        global $plugin_cf, $_Realblog_controller;

        $t = '';
        if ($plugin_cf['realblog']['teaser_multicolumns']) {
            $t .= '<div class="realblog_single_entry_preview">'
                . '<div class="realblog_single_entry_preview_in">';
        }
        $t .= $this->renderArticleHeading($article);
        $t .= $this->renderArticleDate($article);
        $t .= "\n" . '<div class="realblog_show_story">' . "\n";
        $t .= evaluate_scripting($article->teaser);
        if ($plugin_cf['realblog']['show_read_more_link']
            && $article->body_length
        ) {
            $t .= $this->renderArticleFooter($article);
        }
        $t .= '<div style="clear: both;"></div>' . "\n"
            . '</div>' . "\n";
        if ($plugin_cf['realblog']['teaser_multicolumns']) {
            $t .= '</div>' . "\n" . '</div>' . "\n";
        }
        return $t;
    }

    /**
     * Renders an article heading.
     *
     * @param stdClass $article An article.
     *
     * @return string (X)HTML.
     *
     * @global string     The URL of the current page.
     * @global array      The localization of the plugins.
     * @global Controller The plugin controller.
     */
    protected function renderArticleHeading(stdClass $article)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $t = '<h4>';
        $url = $_Realblog_controller->url(
            $su, $article->title, array(
                'realblogID' => $article->id
            )
        );
        if ($article->body_length || XH_ADM) {
            $t .= '<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">';
        }
        $t .= $article->title;
        if ($article->body_length || XH_ADM) {
            $t .= '</a>';
        }
        $t .= '</h4>' . "\n";
        return $t;
    }

    /**
     * Renders an article date.
     *
     * @param stdClass $article An article.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderArticleDate(stdClass $article)
    {
        global $plugin_tx;

        return '<div class="realblog_show_date">'
            . date($plugin_tx['realblog']['date_format'], $article->date)
            . '</div>';
    }

    /**
     * Renders an article footer.
     *
     * @param stdClass $article An article.
     *
     * @return string (X)HTML.
     *
     * @global string     The URL of the current page.
     * @global array      The configuration of the plugins.
     * @global array      The localization of the plugins.
     * @global Controller The plugin controller.
     */
    protected function renderArticleFooter(stdClass $article)
    {
        global $su, $plugin_cf, $plugin_tx, $_Realblog_controller;

        $t = '<div class="realblog_entry_footer">';

        $pcf = $plugin_cf['realblog'];
        if ($pcf['comments_plugin']
            && class_exists($pcf['comments_plugin'] . '_RealblogBridge')
            && $article->commentable
        ) {
            $t .= $this->renderCommentCount($article);
        }
        $url = $_Realblog_controller->url(
            $su, $article->title, array(
                'realblogID' => $article->id
            )
        );
        $t .= '<p class="realblog_read_more">'
            . '<a href="' . XH_hsc($url) . '" title="'
            . $plugin_tx['realblog']["tooltip_view"] . '">'
            . $plugin_tx['realblog']['read_more'] . '</a></p>'
            . '</div>';
        return $t;
    }

    /**
     * Renders a comment count.
     *
     * @param stdClass $article An article.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    protected function renderCommentCount(stdClass $article)
    {
        global $plugin_cf, $plugin_tx;

        $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
        $commentsId = 'comments' . $article->id;
        $count = call_user_func(array($bridge, 'count'), $commentsId);
        $key = 'message_comments' . XH_numberSuffix($count);
        return '<p class="realblog_number_of_comments">'
            . sprintf($plugin_tx['realblog'][$key], $count) . '</p>';
    }
}

?>
