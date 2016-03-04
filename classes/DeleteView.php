<?php

/**
 * The delete views.
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

/**
 * The delete views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_DeleteView extends Realblog_ConfirmationView
{
    /**
     * Initializes a new instance.
     *
     * @return void
     *
     * @global string The title of the page.
     * @global array  The localization of the plugins.
     */
    public function __construct()
    {
        global $title, $plugin_tx;

        parent::__construct();
        $this->buttonLabel = $plugin_tx['realblog']['btn_delete'];
        $title = $this->title = $plugin_tx['realblog']['tooltip_deleteall'];
    }

    /**
     * Renders the delete confirmation.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderConfirmation()
    {
        global $sn, $plugin_tx;

        $o = '<h1>Realblog &ndash; ' . $this->title . '</h1>';
        $o .= '<form name="confirm" method="post" action="' . $sn
            . '?&amp;realblog&amp;admin=plugin_main">'
            . $this->renderHiddenFields('do_delselected');
        $o .= '<table width="100%">';
        $o .= '<tr><td class="reablog_confirm_info" align="center">'
            . $plugin_tx['realblog']['confirm_deleteall']
            . '</td></tr><tr><td>&nbsp;</td></tr>';
        $o .= '<tr><td class="reablog_confirm_button" align="center">'
            . $this->renderConfirmationButtons()
            . '</td></tr>';
        $o .= '</table></form>';
        return $o;
    }
}

?>
