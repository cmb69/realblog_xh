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

class ArticlesAdminView
{
    /**
     * @var string
     */
    protected $imageFolder;

    /**
     * @var array<stdClass>
     */
    protected $articles;

    /**
     * @var int
     */
    protected $articleCount;

    /**
     * @var int
     */
    protected $pageCount;

    /**
     * @param array<stdClass> $articles
     * @param int $articleCount
     * @param int $pageCount
     * @global array $pth
     */
    public function __construct(array $articles, $articleCount, $pageCount)
    {
        global $pth;

        $this->imageFolder =  $pth['folder']['plugins'] . 'realblog/images/';
        $this->articles = $articles;
        $this->articleCount = (int) $articleCount;
        $this->pageCount = (int) $pageCount;
    }

    /**
     * @return string
     * @global string $sn
     * @global Controller $_Realblog_controller
     * @global array $plugin_cf
     * @global string $sn
     */
    public function render()
    {
        global $sn, $_Realblog_controller, $plugin_cf, $sn;

        $page = $_Realblog_controller->getPage();
        if ($plugin_cf['realblog']['pagination_top'] || $plugin_cf['realblog']['pagination_bottom']) {
            $url = "$sn?&realblog&admin=plugin_main&action=plugin_text&realblog_page=%s";
            $pagination = new PaginationView($this->articleCount, $page, $this->pageCount, $url);
        }
        $html = $this->renderFilterForm()
            . '<form method="post" action="' . $sn
            . '?&amp;realblog&amp;admin=plugin_main">'
            . '<table class="realblog_table">'
            . $this->renderTableHead();
        foreach ($this->articles as $article) {
            $html .= $this->renderRow($article);
        }
        if ($plugin_cf['realblog']['pagination_top']) {
            $html .= $pagination->render();
        }
        $html .= '</table>'
            . tag('input type="hidden" name="page" value="' . $page . '"')
            . '</form>';
        if ($plugin_cf['realblog']['pagination_bottom']) {
            $html .= $pagination->render();
        }
        return $html;
    }

    /**
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     */
    private function renderFilterForm()
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
     * @param int $number
     * @param string $name
     * @return string
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderFilterCheckbox($number, $name)
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
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     */
    private function renderTableHead()
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
     * @param stdClass $article
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderRow(stdClass $article)
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        $page = $_Realblog_controller->getPage();
        return '<tr>'
            . '<td class="realblog_table_line">'
            . tag(
                'input type="checkbox" name="realblogtopics[]"'
                . ' value="' . $article->id . '"'
            )
            . '</td>'
            . '<td class="realblog_table_line">'
            . '<a href="' . $sn. '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=delete_realblog&amp;realblogID=' . $article->id
            . '&amp;page=' . $page . '">'
            . tag(
                'img src="' . $this->imageFolder . 'delete.png"' . ' title="'
                . $plugin_tx['realblog']['tooltip_delete'] . '" alt="'
                . $plugin_tx['realblog']['tooltip_delete'] . '"'
            )
            . '</a></td>'
            . '<td class="realblog_table_line">'
            . '<a href="' . $sn . '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=modify_realblog&amp;realblogID=' . $article->id
            . '&amp;page=' . $page . '">'
            . tag(
                'img src="' . $this->imageFolder . 'edit.png"' . ' title="'
                . $plugin_tx['realblog']['tooltip_modify'] . '" alt="'
                . $plugin_tx['realblog']['tooltip_modify'] . '"'
            )
            . '</a></td>'
            . '<td class="realblog_table_line">' . $article->id . '</td>'
            . '<td class="realblog_table_line">'
            . date($plugin_tx['realblog']['date_format'], $article->date)
            . '</td>'
            . '<td class="realblog_table_line">' . $article->status . '</td>'
            . '<td class="realblog_table_line">' . $article->feedable . '</td>'
            . '<td class="realblog_table_line">' . $article->commentable
            . '</td>'
            . '</tr>'
            . '<tr><td colspan="8" class="realblog_table_title">'
            . $article->title . '</td></tr>';
    }
}
