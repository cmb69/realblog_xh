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

class SearchFormView
{
    /**
     * @var int
     */
    protected $year;

    /**
     * @param int $year
     */
    public function __construct($year)
    {
        $this->year = (int) $year;
    }

    /**
     * @return string
     * @global string $sn
     * @global array $tx
     * @global array $plugin_tx
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
     * @return string
     * @global string $su
     */
    protected function renderHiddenInputs()
    {
        global $su;

        return $this->renderHiddenInput('selected', $su);
    }

    /**
     * @param string $name
     * @param string $value
     * @return string
     */
    protected function renderHiddenInput($name, $value)
    {
        return tag(
            'input type="hidden" name="' . $name . '" value="' . $value . '"'
        );
    }
}
