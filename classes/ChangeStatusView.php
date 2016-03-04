<?php

/**
 * The change status views.
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
 * The change status views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class ChangeStatusView extends ConfirmationView
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
        $this->buttonLabel = $plugin_tx['realblog']['btn_ok'];
        $title = $this->title = $plugin_tx['realblog']['tooltip_changestatus'];
    }

    /**
     * Renders the change status confirmation.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderConfirmation()
    {
        global $sn, $plugin_tx;

        $html = '<h1>Realblog &ndash; ' . $this->title . '</h1>'
            . '<form name="confirm" method="post" action="' . $sn
            . '?&amp;' . 'realblog' . '&amp;admin=plugin_main">'
            . $this->renderHiddenFields('do_batchchangestatus')
            . '<table width="100%">'
            . '<tr><td width="100%" align="center">'
            . $this->renderStatusSelect() . '</td></tr>'
            . '<tr><td class="realblog_confirm_info" align="center">'
            . $plugin_tx['realblog']['confirm_changestatus']
            . '</td></tr>'
            . '<tr><td>&nbsp;</td></tr>'
            . '<tr><td class="realblog_confirm_button" align="center">'
            . $this->renderConfirmationButtons() . '</td></tr>'
            . '</table></form>';
        return $html;
    }

    /**
     * Renders the status select.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderStatusSelect()
    {
        global $plugin_tx;

        $states = array(
            'label_status', 'readyforpublishing', 'published', 'archived'
        );
        $html = '<select name="new_realblogstatus">';
        foreach ($states as $i => $state) {
            $value = $i - 1;
            $html .= '<option value="' . $value . '">'
                . $plugin_tx['realblog'][$state] . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}

?>
