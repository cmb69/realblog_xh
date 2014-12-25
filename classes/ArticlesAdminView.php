<?php

/**
 * The articles administration views.
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
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The articles administration views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticlesAdminView
{
    /**
     * The path of the plugin image folder.
     *
     * @var string
     */
    protected $imageFolder;

    /**
     * The articles.
     *
     * @var array
     */
    protected $articles;

    /**
     * The number of articles per page.
     *
     * @var int
     */
    protected $articlesPerPage;

    /**
     * The start index.
     *
     * @var int
     */
    protected $startIndex;

    /**
     * The number of pages.
     *
     * @var int
     */
    protected $pageCount;

    /**
     * Initializes a new instance.
     *
     * @param array $articles        An array of articles.
     * @param int   $articlesPerPage The number of articles per page.
     * @param int   $startIndex      A start index.
     * @param int   $pageCount       The number of pages.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
    public function __construct($articles, $articlesPerPage, $startIndex, $pageCount)
    {
        global $pth;

        $this->imageFolder =  $pth['folder']['plugins'] . 'realblog/images/';
        $this->articles = $articles;
        $this->articlesPerPage = (int) $articlesPerPage;
        $this->startIndex = (int) $startIndex;
        $this->pageCount = (int) $pageCount;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global string              The script name.
     * @global Realblog_Controller The plugin controller.
     */
    public function render()
    {
        global $sn, $_Realblog_controller;

        $html = $this->renderFilterForm()
            . '<form method="post" action="' . $sn
            . '?&amp;realblog&amp;admin=plugin_main">'
            . '<table class="realblog_table">'
            . $this->renderTableHead();
        $page = $_Realblog_controller->getPage();
        $endIndex = $page * $this->articlesPerPage - 1;
        for ($i = $this->startIndex; $i <= $endIndex; $i++) {
            if ($i <= count($this->articles) - 1) {
                $field = $this->articles[$i];
                $html .= $this->renderRow($field);
            }
        }

        $html .= '</table>'
            . tag('input type="hidden" name="page" value="' . $page . '"')
            . '</form>'
            . $this->renderNavigation();
        return $html;
    }

    /**
     * Renders the filter form.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderFilterForm()
    {
        global $sn, $plugin_tx;

        $url = $sn . '?&realblog&admin=plugin_main&action=plugin_text';
        $html = '<form class="realblog_filter" method="post"'
            . ' action="' . XH_hsc($url) . '">';
        $states = array('readyforpublishing', 'published', 'archived');
        foreach ($states as $i => $state) {
            $html .= $this->renderFilterCheckbox($i + 1, $state);
        }
        $html .= '<button>' . $plugin_tx['realblog']['btn_filter'] . '</button>'
            . '</form>';
        return $html;
    }

    /**
     * Renders a filter checkbox and its label.
     *
     * @param int    $number A filter number.
     * @param string $name   A filter name.
     *
     * @return string (X)HTML.
     *
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderFilterCheckbox($number, $name)
    {
        global $plugin_tx, $_Realblog_controller;

        $filterName = 'realblog_filter' . $number;
        $checked = $_Realblog_controller->getFilter($number)
            ? ' checked="checked"'
            : '';
        return tag('input type="hidden" name="' . $filterName . '" value=""')
            . '<label>'
            . tag(
                'input type="checkbox" name="' . $filterName . '" ' . $checked
            )
            . $plugin_tx['realblog'][$name] . '</label>';
    }

    /**
     * Renders the head of the table.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderTableHead()
    {
        global $sn, $plugin_tx;

        return '<tr>'
            . '<td class="realblog_table_header">'
            . '<button name="action" value="batchdelete"  title="'
            . $plugin_tx['realblog']['tooltip_deleteall'] . '">'
            . tag(
                'img src="' . $this->imageFolder  . 'delete.png" alt="'
                . $plugin_tx['realblog']['tooltip_deleteall'] . '"'
            )
            . '</button></td>'
            . '<td class="realblog_table_header">'
            . '<button name="action" value="change_status"  title="'
            . $plugin_tx['realblog']['tooltip_changestatus'] . '">'
            . tag(
                'img src="' . $this->imageFolder  . 'change-status.png" alt="'
                . $plugin_tx['realblog']['tooltip_changestatus'] . '"'
            )
            . '</button></td>'

            . '<td class="realblog_table_header">'
            . '<a href="' . $sn . '?&amp;realblog'
            . '&amp;admin=plugin_main&amp;action=add_realblog" title="'
            . $plugin_tx['realblog']['tooltip_add'] . '">'
            . tag(
                'img src="' . $this->imageFolder . 'add.png"'
                . ' alt="'
                . $plugin_tx['realblog']['tooltip_add'] . '"'
            )
            . '</a></td>'
            . '<td class="realblog_table_header">'
            . $plugin_tx['realblog']['id_label'] . '</td>'
            . '<td class="realblog_table_header">'
            . $plugin_tx['realblog']['date_label'] . '</td>'
            . '<td class="realblog_table_header">'
            . $plugin_tx['realblog']['label_status'] . '</td>'
            . '<td class="realblog_table_header">'
            . $plugin_tx['realblog']['label_rss'] . '</td>'
            . '<td class="realblog_table_header">'
            . $plugin_tx['realblog']['comments_onoff'] . '</td>'
            . '</tr>';
    }

    /**
     * Renders the pagination navigation.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderNavigation()
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        $page = $_Realblog_controller->getPage();
        $db_total_records = count($this->articles);
        $tmp = ($db_total_records > 0)
            ? $plugin_tx['realblog']['page_label'] . ' : ' . $page .  '/'
                . $this->pageCount
            : '';
        $html = '<div class="realblog_paging_block">'
            . '<div class="realblog_db_info">'
            . $plugin_tx['realblog']['record_count'] . ' : '
            . $db_total_records . '</div>'
            . '<div class="realblog_page_info">' . $tmp . '</div>';
        if ($db_total_records > 0 && $this->pageCount > 1) {
            if ($this->pageCount > $page) {
                $next = $page + 1;
                $back = ($page > 1) ? ($next - 2) : '1';
            } else {
                $next = $this->pageCount;
                $back = $this->pageCount - 1;
            }
            $html .= '<div class="realblog_table_paging">'
                . '<a href="' . $sn . '?&amp;realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text&amp;realblog_page='
                . $back . '" title="' . $plugin_tx['realblog']['tooltip_previous']
                . '">&#9664;</a>&nbsp;&nbsp;';
            for ($i = 1; $i <= $this->pageCount; $i++) {
                $separator = ($i < $this->pageCount) ? ' ' : '';
                $html .= '<a href="' . $sn . '?&amp;realblog&amp;admin=plugin_main'
                    . '&amp;action=plugin_text&amp;realblog_page=' . $i
                    . '" title="' . $plugin_tx['realblog']['page_label']
                    . ' ' . $i . '">[' . $i . ']</a>' . $separator;
            }
            $html .= '&nbsp;&nbsp;<a href="' . $sn . '?&amp;realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text&amp;realblog_page='
                . $next . '" title="' . $plugin_tx['realblog']['tooltip_next']
                . '">&#9654;</a>'
                . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Renders a row.
     *
     * @param Realblog_Article $article An article.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderRow(Realblog_Article $article)
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        $page = $_Realblog_controller->getPage();
        return '<tr>'
            . '<td class="realblog_table_line">'
            . tag(
                'input type="checkbox" name="realblogtopics[]"'
                . ' value="' . $article->getId() . '"'
            )
            . '</td>'
            . '<td class="realblog_table_line">'
            . '<a href="' . $sn. '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=delete_realblog&amp;realblogID=' . $article->getId()
            . '&amp;page=' . $page . '">'
            . tag(
                'img src="' . $this->imageFolder . 'delete.png"' . ' title="'
                . $plugin_tx['realblog']['tooltip_delete'] . '" alt="'
                . $plugin_tx['realblog']['tooltip_delete'] . '"'
            )
            . '</a></td>'
            . '<td class="realblog_table_line">'
            . '<a href="' . $sn . '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=modify_realblog&amp;realblogID=' . $article->getId()
            . '&amp;page=' . $page . '">'
            . tag(
                'img src="' . $this->imageFolder . 'edit.png"' . ' title="'
                . $plugin_tx['realblog']['tooltip_modify'] . '" alt="'
                . $plugin_tx['realblog']['tooltip_modify'] . '"'
            )
            . '</a></td>'
            . '<td class="realblog_table_line">' . $article->getId() . '</td>'
            . '<td class="realblog_table_line">'
            . date($plugin_tx['realblog']['date_format'], $article->getDate())
            . '</td>'
            . '<td class="realblog_table_line">' . $article->getStatus() . '</td>'
            . '<td class="realblog_table_line">' . $article->isFeedable() . '</td>'
            . '<td class="realblog_table_line">' . $article->isCommentable()
            . '</td>'
            . '</tr>'
            . '<tr><td colspan="8" class="realblog_table_title">'
            . $article->getTitle() . '</td></tr>';
    }
}

?>
