<?php

/**
 * The presentation layer.
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
    private $_id;

    /**
     * The article record.
     *
     * @var array
     */
    private $_article;

    /**
     * The article page. Most likely this is always 1.
     *
     * @var int
     */
    private $_page;

    /**
     * Initializes a new instance.
     *
     * @param int    $id      An article ID.
     * @param string $article An article record.
     * @param int    $page    An article page.
     *
     * @return void
     */
    public function __construct($id, $article, $page)
    {
        $this->_id = (int) $id;
        $this->_article = (array) $article;
        $this->_page = (int) $page;
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
            . $this->_renderLinks() . $this->_renderHeading()
            . $this->_renderDate() . $this->_renderStory()
            . $this->_renderLinks() . '</div>';
        // output comments in RealBlog
        if ($this->_wantsComments() && $this->_article[REALBLOG_COMMENTS] == 'on') {
            $realblog_comments_id = 'comments' . $this->_id;
            if ($plugin_cf['realblog']['comments_form_protected'] == 'true') {
                $html .= comments($realblog_comments_id, 'protected');
            } else {
                $html .= comments($realblog_comments_id);
            }
        }
        return $html;
    }

    /**
     * Renders the links.
     *
     * @return string (X)HTML.
     *
     * @global bool Whether we're in admin mode.
     */
    private function _renderLinks()
    {
        global $adm;

        $html = '<div class="realblog_buttons">'
            . $this->_renderOverviewLink();
        if ($adm) {
            if ($this->_wantsComments()) {
                $html .= $this->_renderEditCommentsLink();
            }
            $html .= $this->_renderEditEntryLink();
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
     * @global string The script name.
     * @global string The URL of the current page.
     * @global array  The localization of the plugins.
     */
    private function _renderOverviewLink()
    {
        global $sn, $su, $plugin_tx;

        if ($this->_article[REALBLOG_STATUS] == 2) {
            $url = $sn . '?' . $su . '&amp;realblogYear='
                . $_SESSION['realblogYear'];
            $text = $plugin_tx['realblog']['archiv_back'];
        } else {
            $url = $sn . '?' . $su . '&amp;page=' . $this->_page;
            $text = $plugin_tx['realblog']['blog_back'];
        }
        return '<span class="realblog_button">'
            . '<a href="' . $url . '">' . $text . '</a></span>';
    }

    /**
     * Renders the edit entry link.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderEditEntryLink()
    {
        global $sn, $plugin_tx;

        return '<span class="realblog_button">'
            . '<a href="' . $sn . '?&amp;realblog&amp;admin=plugin_main'
            . '&amp;action=modify_realblog&amp;realblogID='
            . $this->_id . '">'
            . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
    }

    /**
     * Renders the edit comments link.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderEditCommentsLink()
    {
        global $sn, $plugin_tx;

        return '<span class="realblog_button">'
            . '<a href="' . $sn . '?&amp;comments&amp;admin=plugin_main'
            . '&amp;action=plugin_text&amp;selected=comments'
            . $this->_id . '.txt">'
            . $plugin_tx['realblog']['comment_edit'] . '</a></span>';
    }

    /**
     * Renders the article heading.
     *
     * @return string (X)HTML.
     *
     * @todo Heed $cf[menu][levels].
     */
    private function _renderHeading()
    {
        return '<h4>' . $this->_article[REALBLOG_TITLE] . '</h4>';
    }

    /**
     * Renders the article date.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderDate()
    {
        global $plugin_tx;

        $date = strftime(
            $plugin_tx['realblog']['display_date_format'],
            $this->_article[REALBLOG_DATE]
        );
        return '<div class="realblog_show_date">' . $date . '</div>';
    }

    /**
     * Renders the article story.
     *
     * @return string (X)HTML.
     */
    private function _renderStory()
    {
        $story = $this->_article[REALBLOG_STORY] != ''
            ? $this->_article[REALBLOG_STORY]
            : $this->_article[REALBLOG_HEADLINE];
        return '<div class="realblog_show_story_entry">'
            // FIXME: stripslashes() ?
            . stripslashes(evaluate_scripting($story))
            . '</div>';
    }

    /**
     * Returns whether comments are enabled.
     *
     * @return bool
     *
     * @global array The configuration of the plugins.
     */
    private function _wantsComments()
    {
        global $plugin_cf;

        return $plugin_cf['realblog']['comments_function'] == 'true'
            && function_exists('comments');
    }
}

/**
 * The info views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_InfoView
{
    /**
     * Renders the plugin info.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        return '<h1>Realblog</h1>'
            . $this->_renderLogo()
            . '<p>Version: ' . REALBLOG_VERSION . '</p>'
            . $this->_renderCopyright() . $this->_renderLicense();
    }

    /**
     * Renders the plugin logo.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     */
    private function _renderLogo()
    {
        global $pth, $plugin_tx;

        return tag(
            'img src="' . $pth['folder']['plugins']
            . 'realblog/realblog.png" class="realblog_logo"'
            . ' alt="' . $plugin_tx['realblog']['alt_logo'] . '"'
        );
    }

    /**
     * Renders the copyright info.
     *
     * @return string (X)HTML.
     */
    private function _renderCopyright()
    {
        return '<p>Copyright &copy; 2006-2010 Jan Kanters' . tag('br')
            . 'Copyright &copy; 2010-2014 '
            . '<a href="http://www.ge-webdesign.de/" target="_blank">'
            . 'Gert Ebersbach</a>' . tag('br')
            . 'Copyright &copy; 2014 '
            . '<a href="http://3-magi.net/" target="_blank">'
            . 'Christoph M. Becker</a></p>';
    }

    /**
     * Renders the license info.
     *
     * @return string (X)HTML.
     */
    private function _renderLicense()
    {
        return <<<EOT
<p class="realblog_license">This program is free software: you can redistribute
it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.</p>
<p class="realblog_license">This program is distributed in the hope that it will
be useful, but <em>without any warranty</em>; without even the implied warranty
of <em>merchantability</em> or <em>fitness for a particular purpose</em>. See
the GNU General Public License for more details.</p>
<p class="realblog_license">You should have received a copy of the GNU General
Public License along with this program. If not, see <a
href="http://www.gnu.org/licenses/"
target="_blank">http://www.gnu.org/licenses/</a>.</p>
EOT;
    }
}

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
    private $_imageFolder;

    /**
     * Initializes a new instance.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
    public function __construct()
    {
        global $pth;

        $this->_imageFolder =  $pth['folder']['plugins'] . 'realblog/images/';
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The current page number.
     * @global int    The number of articles per page.
     * @global int    The start index of the first article on the page.
     * @global int    The article count.
     * @global array  The article records.
     */
    public function render()
    {
        global $sn, $page, $page_record_limit, $start_index, $db_total_records,
            $records;

        $o = $this->_renderFilterForm();
        // Display table header
        $o .= "\n" . '<div>' . "\n"
            . '<form method="post" action="' . $sn . '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main&amp;action=plugin_text">' . "\n"
            . '<table class="realblog_table" width="100%" cellpadding="0"'
            . ' cellspacing="0">';
        $o .= $this->_renderTableHead();

        $end_index = $page * $page_record_limit - 1;

        // Display table lines
        for ($i = $start_index; $i <= $end_index; $i++) {
            if ($i > $db_total_records - 1) {
                $o .= $this->_renderEmptyRow();
            } else {
                $field = $records[$i];
                $o .= $this->_renderRow($field);
            }
        }

        $o .= '</table></div>';
        $o .= tag('input type="hidden" name="page" value="' . $page . '"')
            . '</form><div>&nbsp;</div>';
        $o .= $this->_renderNavigation();
        return $o;
    }

    /**
     * Renders the filter form.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string Whether filter 1 is enabled.
     * @global string Whether filter 2 is enabled.
     * @global string Whether filter 3 is enabled.
     */
    private function _renderFilterForm()
    {
        global $sn, $plugin_tx, $filter1, $filter2, $filter3;

        $tstfilter1 = ($filter1 == 'on') ? ' checked="checked"' : '';
        $tstfilter2 = ($filter2 == 'on') ? ' checked="checked"' : '';
        $tstfilter3 = ($filter3 == 'on') ? ' checked="checked"' : '';
        return '<div>'
            . '<form name="selectstatus" method="post" action="' . $sn
            . '?&amp;realblog&amp;admin=plugin_main&amp;action=plugin_text">'
            . '<table width="100%">' . '<tr>'
            . '<td width="35%">'
            . tag('input type="checkbox" name="filter1" ' . $tstfilter1 . '"')
            . '&nbsp;' . $plugin_tx['realblog']['readyforpublishing'] . '</td>'
            . '<td width="30%">'
            . tag('input type="checkbox" name="filter2"' . $tstfilter2 . '"')
            . '&nbsp;' . $plugin_tx['realblog']['published'] . '</td>'
            . '<td width="30%">'
            . tag('input type="checkbox" name="filter3"' . $tstfilter3 . '"')
            . '&nbsp;' . $plugin_tx['realblog']['archived'] . '</td>'
            . '<td width="5%">'
            . tag(
                'input type="image" align="middle" src="'
                . $this->_imageFolder . 'filter.png" name="send"'
                . ' value="Apply filter" title="'
                . $plugin_tx['realblog']['btn_search']
                . '"'
            )
            . '</td>'
            . '</tr>' . '</table>'
            . tag('input type="hidden" name="filter" value="true"')
            . '</form>' . "\n" . '</div>';
    }

    /**
     * Renders the head of the table.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    private function _renderTableHead()
    {
        global $sn, $plugin_tx;

        return '<tr>'
            . '<td class="realblog_table_header" align="center">'
            . tag(
                'input type="image" align="middle" src="'
                . $this->_imageFolder . 'delete.png" name="batchdelete"'
                . ' value="true" title="'
                . $plugin_tx['realblog']['tooltip_deleteall'] . '"'
            )
            . '</td>'
            . '<td class="realblog_table_header" align="center">'
            . tag(
                'input type="image" align="middle" src="' . $this->_imageFolder
                . 'change-status.png" name="changestatus" value="true"'
                . ' title="' . $plugin_tx['realblog']['tooltip_changestatus']
                . '"'
            )
            . '</td>'
            . '<td class="realblog_table_header" align="center">'
            . '<a href="' . $sn . '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main&amp;action=add_realblog">'
            . tag(
                'img src="' . $this->_imageFolder . 'add.png"'
                . ' align="middle" title="'
                . $plugin_tx['realblog']['tooltip_add'] . '" alt=""'
            )
            . '</a></td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . $plugin_tx['realblog']['id_label'] . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . $plugin_tx['realblog']['date_label'] . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . 'Status' . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">RSS Feed'
            . '</td>' . "\n"
            . '<td class="realblog_table_header" align="center">'
            . $plugin_tx['realblog']['comments_onoff'] . '</td>' . "\n"
            . '</tr>';
    }

    /**
     * Renders the pagination navigation.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string Whether the filter form has been submitted.
     * @global string Whether filter 1 is enabled.
     * @global string Whether filter 2 is enabled.
     * @global string Whether filter 3 is enabled.
     * @global string The current page number.
     * @global int    The number of pages.
     * @global int    The number of articles.
     */
    private function _renderNavigation()
    {
        global $sn, $plugin_tx, $filter, $filter1, $filter2, $filter3, $page,
            $page_total, $db_total_records;

        $tmp = ($db_total_records > 0)
            ? $plugin_tx['realblog']['page_label'] . ' : ' . $page .  ' / '
                . $page_total
            : '';
        $o = '<div class="realblog_paging_block">'
            . '<div class="realblog_db_info">'
            . $plugin_tx['realblog']['record_count'] . ' : '
            . $db_total_records . '</div>'
            . '<div class="realblog_page_info">&nbsp;&nbsp;&nbsp;' . $tmp
            . '</div>';

        if ($db_total_records > 0 && $page_total > 1) {
            if ($page_total > $page) {
                $next = $page + 1;
                $back = ($page > 1) ? ($next - 2) : '1';
            } else {
                $next = $page_total;
                $back = $page_total - 1;
            }
            $o .= '<div class="realblog_table_paging">'
                . '<a href="' . $sn . '?&amp;' . 'realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                . $back . '&amp;filter1=' . $filter1 . '&amp;filter2='
                . $filter2 . '&amp;filter3=' . $filter3 . '&amp;filter='
                . $filter . '" title="'
                . $plugin_tx['realblog']['tooltip_previous'] . '">'
                . '&#9664;</a>&nbsp;&nbsp;';
            for ($i = 1; $i <= $page_total; $i++) {
                $separator = ($i < $page_total) ? ' ' : '';
                $o .= '<a href="' . $sn . '?&amp;' . 'realblog'
                    . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                    . $i . '&amp;filter1=' . $filter1 . '&amp;filter2='
                    . $filter2 . '&amp;filter3=' . $filter3 . '&amp;filter='
                    . $filter . '" title="' . $plugin_tx['realblog']['page_label']
                    . ' ' . $i . '">[' . $i . ']</a>' . $separator;
            }
            $o .= '&nbsp;&nbsp;<a href="' . $sn . '?&amp;' . 'realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text&amp;page='
                . $next . '&amp;filter1=' . $filter1 . '&amp;filter2='
                . $filter2 . '&amp;filter3=' . $filter3 . '&amp;filter='
                . $filter . '" title="' . $plugin_tx['realblog']['tooltip_next']
                . '">'
                . '&#9654;</a>';
            $o .= '</div>';
        }
        $o .= '</div>';
        return $o;
    }

    /**
     * Renders a row.
     *
     * @param array $field An article record.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string The current page number.
     */
    private function _renderRow($field)
    {
        global $sn, $plugin_tx, $page;

        return '<tr>'
            . '<td class="realblog_table_line" align="center">'
            . tag(
                'input type="checkbox" name="realblogtopics[]"'
                . ' value="' . $field[REALBLOG_ID] . '"'
            )
            . '</td>'
            . '<td class="realblog_table_line" valign="top"'
            . ' align="center">'
            . '<a href="' . $sn. '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main&amp;action=delete_realblog'
            . '&amp;realblogID=' . $field[REALBLOG_ID] . '&amp;page='
            . $page . '">'
            . tag(
                'img src="' . $this->_imageFolder . 'delete.png"'
                . ' align="center" title="'
                . $plugin_tx['realblog']['tooltip_delete'] . '" alt=""'
            )
            . '</a></td>'
            . '<td class="realblog_table_line" valign="top"'
            . ' align="center">'
            . '<a href="' . $sn . '?&amp;' . 'realblog'
            . '&amp;admin=plugin_main&amp;action=modify_realblog'
            . '&amp;realblogID=' . $field[REALBLOG_ID] . '&amp;page='
            . $page . '">'
            . tag(
                'img src="' . $this->_imageFolder . 'edit.png"'
                . ' align="center" title="'
                . $plugin_tx['realblog']['tooltip_modify'] . '" alt=""'
            )
            . '</a></td>'
            . '<td class="realblog_table_line" valign="top"'
            . ' align="center"><b>' . $field[REALBLOG_ID] . '</b></td>'
            . '<td valign="top" style="text-align: center;"'
            . ' class="realblog_table_line">'
            . date(
                $plugin_tx['realblog']['date_format'], $field[REALBLOG_DATE]
            )
            . '</td>' . "\n"
            . '<td class="realblog_table_line" valign="top"'
            . ' style="text-align: center;"><b>'
            . $field[REALBLOG_STATUS] . '</b></td>' . "\n"
            . '<td class="realblog_table_line realblog_onoff"'
            . ' valign="top" style="text-align: center;">'
            . $field[REALBLOG_RSSFEED] . '</td>' . "\n"
            . '<td class="realblog_table_line realblog_onoff"'
            . ' valign="top" style="text-align: center;">'
            . $field[REALBLOG_COMMENTS] . '</td>' . "\n"
            . '</tr>' . "\n" . '<tr>' . "\n"
            . '<td colspan="8" valign="top"'
            . ' class="realblog_table_title"><span>'
            . $field[REALBLOG_TITLE] . '</span></td></tr>';
    }

    /**
     * Renders an empty row.
     *
     * @return string (X)HTML.
     *
     * @todo Simply remove this?
     */
    private function _renderEmptyRow()
    {
        $html = '<tr>';
        for ($i = 0; $i < 5; $i++) {
            $html .= '<td class="realblog_table_line" align="center">&nbsp;</td>';
        }
        for ($i = 0; $i < 3; $i++) {
            $html .= '<td class="realblog_table_line">&nbsp;</td>';
        }
        $html .= '</tr>';
        return $html;
    }
}

?>
