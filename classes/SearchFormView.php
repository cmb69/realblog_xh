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
            . $this->renderInputRow()
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
     * @return string (X)HTML.
     */
    protected function renderInputRow()
    {
        return '<tr><td style="width: 30%;" class="realblog_search_text"></td>'
            . '<td>' . $this->renderInput("realblog_search") . '</td></tr>';
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

}

?>
