<?php

/**
 * The search form views.
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
 * The search form views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_SearchFormView
{
    /**
     * The selected year.
     *
     * @var int
     */
    protected $year;

    /**
     * Initializes a new instance.
     *
     * @param int $year A year.
     *
     * @return void
     */
    public function __construct($year)
    {
        $this->year = (int) $year;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global string The (X)HTML fragment to insert into the head element.
     * @global array  The localization of the core.
     * @global array  The localization of the plugins.
     */
    public function render()
    {
        global $sn, $hjs, $tx, $plugin_tx;

        $hjs .= $this->renderToggleScript();
        return '<form name="realblogsearch" method="get" action="' . $sn . '">'
            . $this->renderSearchToggle()
            . '<div id="searchblock" style="display: none">'
            . $this->renderHiddenInputs()
            . '<p class="realblog_search_hint">'
            . $plugin_tx['realblog']['search_hint'] . '</p>'
            . '<table style="width: 100%;">'
            . $this->renderInputRow('title')
            . '<tr>'
            . '<td style="width: 30%;">&nbsp;</td>'
            . '<td>' . $this->renderOperatorRadio('and') . '&nbsp;&nbsp;&nbsp;'
            . $this->renderOperatorRadio('or') . '</td>'
            . '</tr>'
            . $this->renderInputRow('story')
            . '<tr><td colspan="2" style="text-align: center;">'
            . tag(
                'input type="submit" value="'
                . $tx['search']['button'] . '"'
            )
            . '</td></tr>'
            . '</table></div></form>';
    }

    /**
     * Renders the search toggle.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the core.
     * @global array The localization of the plugins.
     */
    protected function renderSearchToggle()
    {
        global $pth, $tx, $plugin_tx;

        $tag = <<<EOT
img id="realblog_search_toggle" class="realblog_search_toggle"
src="{$pth['folder']['plugins']}realblog/images/btn_expand.gif"
alt="{$plugin_tx['realblog']['tooltip_showsearch']}"
title="{$plugin_tx['realblog']['tooltip_showsearch']}"
onclick="realblog_showSearch()"
EOT;
        return tag($tag)
            . '<span class="realblog_search_caption">'
            . $tx['search']['button'] . '</span>';
    }

    /**
     * Renders the toggle script.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     *
     * @todo Escape JS strings.
     */
    protected function renderToggleScript()
    {
        global $pth, $plugin_tx;

        $imageFolder = $pth['folder']['plugins'] . 'realblog/images/';
        return <<<EOT
<script type="text/javascript">/* <![CDATA[ */
function realblog_showSearch() {
    var searchblock = document.getElementById("searchblock"),
        toggle = document.getElementById("realblog_search_toggle");

    if (searchblock.style.display == "none") {
        toggle.title = "{$plugin_tx['realblog']['tooltip_hidesearch']}";
        toggle.src = "{$imageFolder}btn_collapse.gif";
        searchblock.style.display = "";
    } else {
        toggle.title = "{$plugin_tx['realblog']['tooltip_showsearch']}";
        toggle.src = "{$imageFolder}btn_expand.gif";
        searchblock.style.display = "none";
    }
}
/* ]]> */s</script>
EOT;
    }

    /**
     * Renders the hidden input fields.
     *
     * @return string (X)HTML.
     *
     * @global string The URL of the current page.
     */
    protected function renderHiddenInputs()
    {
        global $su;

        return $this->renderHiddenInput('selected', $su);
    }

    /**
     * Renders a hidden input field.
     *
     * @param string $name  A name.
     * @param string $value A value.
     *
     * @return string (X)HTML.
     */
    protected function renderHiddenInput($name, $value)
    {
        return tag(
            'input type="hidden" name="' . $name . '" value="' . $value . '"'
        );
    }

    /**
     * Renders an input row.
     *
     * @param string $which Which row to render ('title' or 'story').
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderInputRow($which)
    {
        global $plugin_tx;

        return '<tr><td style="width: 30%;" class="realblog_search_text">'
            . $plugin_tx['realblog']["{$which}_label"] . ' '
            . $plugin_tx['realblog']['search_contains'] . ':' . '</td>'
            . '<td>'
            // TODO: make the operators available?
            /*. $this->renderOperatorSelect("{$which}_operator")*/
            . $this->renderInput("realblog_$which") . '</td></tr>';
    }

    /**
     * Renders an input field.
     *
     * @param string $name A name.
     *
     * @return string (X)HTML.
     */
    protected function renderInput($name)
    {
        return tag(
            'input type="text" name="' . $name . '" size="35"'
            . ' class="realblog_search_input" maxlength="64"'
        );
    }

    /**
     * Renders an operator select element.
     *
     * @param string $name A name.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderOperatorSelect($name)
    {
        global $plugin_tx;

        return '<select name="' . $name . '" style="display: none">'
            . '<option value="2" selected="selected">'
            . $plugin_tx['realblog']['search_contains'] . '</option>'
            . '</select>';
    }

    /**
     * Renders an operator radio element.
     *
     * @param string $which Which operator to render ('and' or 'or').
     *
     * @return string (X)HTML.
     *
     * @global array The localiaztion of the plugins.
     */
    protected function renderOperatorRadio($which)
    {
        global $plugin_tx;

        $checked = ($which == 'or') ? ' checked="checked"' : '';
        return '<label>'
            . tag(
                'input type="radio" name="realblog_search"'
                . ' value="' . strtoupper($which) . '"' . $checked
            )
            . '&nbsp;' . $plugin_tx['realblog']["search_$which"] . '</label>';
    }
}

?>
