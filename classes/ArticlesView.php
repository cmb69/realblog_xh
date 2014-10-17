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
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The articles views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticlesView
{
    /**
     * The articles.
     *
     * @var array
     */
    protected $articles;

    /**
     * The categories.
     *
     * @var string
     */
    protected $categories;

    /**
     * The number of articles per page.
     *
     * @var int
     */
    protected $articlesPerPage;

    /**
     * Initializes a new instance.
     *
     * @param array  $articles        An array of articles.
     * @param string $categories      FIXME
     * @param int    $articlesPerPage The number of articles per page.
     *
     * @return void
     */
    public function __construct($articles, $categories, $articlesPerPage)
    {
        $this->articles = $articles;
        $this->categories = (string) $categories;
        $this->articlesPerPage = (int) $articlesPerPage;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global array               The configuration of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    public function render()
    {
        global $plugin_cf, $_Realblog_controller;

        $articleCount = count($this->articles);
        $pageCount = (int) ceil($articleCount / $this->articlesPerPage);
        $page = $_Realblog_controller->getPage();
        if ($page > $pageCount) {
            $page = 1;
        }
        if ($page <= 1) {
            $start_index = 0;
            $page = 1;
        } else {
            $start_index = ($page - 1) * $this->articlesPerPage;
        }
        $end_index = min($page * $this->articlesPerPage - 1, $articleCount);

        if ($articleCount > 0 && $pageCount > 1) {
            if ($pageCount > $page) {
                $next = $page + 1;
                $back = ($page > 1) ? $next - 2 : "1";
            } else {
                $next = $pageCount;
                $back = $pageCount - 1;
            }
        }

        $t = "\n" . '<div class="realblog_show_box">' . "\n";
        $t .= $this->renderPagination(
            'top', $page, $pageCount, @$back, @$next
        );
        $t .= "\n" . '<div style="clear:both;"></div>';
        $t .= $this->renderArticlePreviews($start_index, $end_index);
        $t .= $this->renderPagination(
            'bottom', $page, $pageCount, @$back, @$next
        );
        $t .= '<div style="clear: both"></div></div>';
        return $t;
    }

    /**
     * Renders the article previews.
     *
     * @param int $start The first article to render.
     * @param int $end   The last article to render.
     *
     * @return string (X)HTML.
     */
    protected function renderArticlePreviews($start, $end)
    {
        $articleCount = count($this->articles);
        $t = '<div id="realblog_entries_preview" class="realblog_entries_preview">';
        for ($i = $start; $i <= $end; $i++) {
            if ($i > $articleCount - 1) {
                $t .= '';
            } else {
                $article = $this->articles[$i];
                $t .= $this->renderArticlePreview($article);
            }
        }
        $t .= '<div style="clear: both;"></div>' . '</div>';
        return $t;
    }

    /**
     * Renders an article preview.
     *
     * @param Realblog_Article $article An article.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderArticlePreview(Realblog_Article $article)
    {
        global $plugin_cf, $_Realblog_controller;

        $t = '';
        if (strstr($article->getTeaser(), '|' . $this->categories . '|')
            || strstr($article->getBody(), '|' . $this->categories . '|')
            || $this->categories == 'all'
            || ($_Realblog_controller->getPgParameter('realblog_search')
            && strstr($article->getTeaser(), '|' . $this->categories . '|'))
        ) {
            if ($plugin_cf['realblog']['teaser_multicolumns']) {
                $t .= '<div class="realblog_single_entry_preview">'
                    . '<div class="realblog_single_entry_preview_in">';
            }
            $t .= $this->renderArticleHeading($article);
            $t .= $this->renderArticleDate($article);
            $t .= "\n" . '<div class="realblog_show_story">' . "\n";
            $t .= evaluate_scripting($article->getTeaser());
            if ($plugin_cf['realblog']['show_read_more_link']
                && $article->getBody() != ''
            ) {
                $t .= $this->renderArticleFooter($article);
            }
            $t .= '<div style="clear: both;"></div>' . "\n"
                . '</div>' . "\n";
            if ($plugin_cf['realblog']['teaser_multicolumns']) {
                $t .= '</div>' . "\n" . '</div>' . "\n";
            }
        }
        return $t;
    }

    /**
     * Renders an article heading.
     *
     * @param Realblog_Article $article An article.
     *
     * @return string (X)HTML.
     *
     * @global string              The URL of the current page.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderArticleHeading(Realblog_Article $article)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $t = '<h4>';
        $url = $_Realblog_controller->url(
            $su, $article->getTitle(), array(
                'realblogID' => $article->getId()
            )
        );
        if ($article->getBody() != '' || XH_ADM) {
            $t .= '<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']["tooltip_view"] . '">';
        }
        $t .= $article->getTitle();
        if ($article->getBody() != '' || XH_ADM) {
            $t .= '</a>';
        }
        $t .= '</h4>' . "\n";
        return $t;
    }

    /**
     * Renders an article date.
     *
     * @param Realblog_Article $article An article.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderArticleDate(Realblog_Article $article)
    {
        global $plugin_tx;

        return '<div class="realblog_show_date">'
            . date($plugin_tx['realblog']['date_format'], $article->getDate())
            . '</div>';
    }

    /**
     * Renders an article footer.
     *
     * @param Realblog_Article $article An article.
     *
     * @return string (X)HTML.
     *
     * @global string The URL of the current page.
     * @global array  The configuration of the plugins.
     * @global array  The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderArticleFooter(Realblog_Article $article)
    {
        global $su, $plugin_cf, $plugin_tx, $_Realblog_controller;

        $t = '<div class="realblog_entry_footer">';

        $pcf = $plugin_cf['realblog'];
        if ($pcf['comments_plugin']
            && class_exists($pcf['comments_plugin'] . '_RealblogBridge')
            && $article->isCommentable()
        ) {
            $t .= $this->renderCommentCount($article);
        }
        $url = $_Realblog_controller->url(
            $su, $article->getTitle(), array(
                'realblogID' => $article->getId()
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
     * @param Realblog_Article $article An article.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    protected function renderCommentCount(Realblog_Article $article)
    {
        global $plugin_cf, $plugin_tx;

        $bridge = $plugin_cf['realblog']['comments_plugin'] . '_RealblogBridge';
        $commentsId = 'comments' . $article->getId();
        $count = call_user_func(array($bridge, count), $commentsId);
        $key = 'message_comments' . XH_numberSuffix($count);
        return '<p class="realblog_number_of_comments">'
            . sprintf($plugin_tx['realblog'][$key], $count) . '</p>';
    }

    /**
     * Renders the pagination.
     *
     * @param string $place     A place to render ('top' or 'bottom').
     * @param string $page      A page number.
     * @param int    $pageCount A page count.
     * @param int    $back      The number of the previous page.
     * @param int    $next      The number of the next page.
     *
     * @return string (X)HTML.
     */
    protected function renderPagination($place, $page, $pageCount, $back, $next)
    {
        $articleCount = count($this->articles);
        $t = '';
        if ($articleCount > 0 && $pageCount > 1) {
            $t .= $this->renderPageLinks($pageCount);
        }
        if ($this->wantsNumberOfArticles($place)) {
            $t .= $this->renderNumberOfArticles();
        }
        if ($articleCount > 0 && $pageCount > 1) {
            $t .= $this->renderPageOfPages(
                $page, $pageCount, $back, $next
            );
        }
        return $t;
    }

    /**
     * Whether the number of articles ought to be displayed.
     *
     * @param string $place A place ('top' or 'bottom').
     *
     * @return bool
     *
     * @global array               The configuration of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function wantsNumberOfArticles($place)
    {
        global $plugin_cf, $_Realblog_controller;

        return is_null($_Realblog_controller->getPgParameter('realblog_story'))
            && $plugin_cf['realblog']['show_numberof_entries_' . $place];
    }

    /**
     * Renders the page links.
     *
     * @param int $pageCount A page count.
     *
     * @return string (X)HTML.
     *
     * @global string              The URL of the current page.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderPageLinks($pageCount)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $t = '<div class="realblog_table_paging">';
        for ($i = 1; $i <= $pageCount; $i++) {
            $separator = ($i < $pageCount) ? ' ' : '';
            $url = $_Realblog_controller->url(
                $su, null, array('realblog_page' => $i)
            );
            $t .= '<a href="' . XH_hsc($url) . '" title="'
                . $plugin_tx['realblog']['page_label'] . ' ' . $i . '">['
                . $i . ']</a>' . $separator;
        }
        $t .= '</div>';
        return $t;
    }

    /**
     * Renders the page of pages.
     *
     * @param string $page      The number of the current page.
     * @param int    $pageCount A page count.
     * @param int    $back      The number of the previous page.
     * @param int    $next      The number of the next page.
     *
     * @return string (X)HTML.
     *
     * @global string              The URL of the current page.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderPageOfPages($page, $pageCount, $back, $next)
    {
        global $su, $plugin_tx, $_Realblog_controller;

        $backUrl = $_Realblog_controller->url(
            $su, null, array('realblog_page' => $back)
        );
        $nextUrl = $_Realblog_controller->url(
            $su, null, array('realblog_page' => $next)
        );
        return '<div class="realblog_page_info">'
            . $plugin_tx['realblog']['page_label'] . ' : '
            . '<a href="' . XH_hsc($backUrl) . '" title="'
            . $plugin_tx['realblog']['tooltip_previous'] . '">'
            . '&#9664;</a>&nbsp;' . $page . '/' . $pageCount
            . '&nbsp;' . '<a href="' . XH_hsc($nextUrl) . '" title="'
            . $plugin_tx['realblog']['tooltip_next'] . '">'
            . '&#9654;</a></div>';
    }

    /**
     * Renders the number of articles.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderNumberOfArticles()
    {
        global $plugin_tx;

        return '<div class="realblog_db_info">'
            . $plugin_tx['realblog']['record_count'] . ' : '
            . count($this->articles) . '</div>';
    }
}

?>
