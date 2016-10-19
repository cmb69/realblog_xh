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
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

namespace Realblog;

/**
 * The search form views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class SearchFormView
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
     * @global array  The localization of the core.
     * @global array  The localization of the plugins.
     */
    public function render()
    {
        global $sn, $tx, $plugin_tx;

        return '<form class="realblog_search_form" method="get" action="' . $sn . '">'
            . $this->renderHiddenInputs()
            . '<p>'
            . tag(
                'input type="text" name="realblog_search" size="15"'
                . ' class="realblog_search_input" maxlength="64" title="'
                . $plugin_tx['realblog']['search_hint'] . '" placeholder="'
                . $plugin_tx['realblog']['search_placeholder'] . '"'
            )
            . tag(
                'input type="submit" value="'
                . $tx['search']['button'] . '"'
            )
            . '</p></form>';
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
}

?>
