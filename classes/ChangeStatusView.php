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

class ChangeStatusView extends ConfirmationView
{
    /**
     * @global string $title
     * @global array $plugin_tx
     */
    public function __construct()
    {
        global $title, $plugin_tx;

        parent::__construct();
        $this->buttonLabel = $plugin_tx['realblog']['btn_ok'];
        $title = $this->title = $plugin_tx['realblog']['tooltip_changestatus'];
    }

    /**
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     */
    protected function renderConfirmation()
    {
        global $sn, $plugin_tx;

        $url = XH_hsc("$sn?&realblog&admin=plugin_main");
        $message = XH_message('warning', $plugin_tx['realblog']['confirm_changestatus']);
        $hiddenFields = $this->renderHiddenFields('do_batchchangestatus');
        $statusSelect = $this->renderStatusSelect();
        $confirmationButton = $this->renderConfirmationButton();
        $overviewLink = $this->renderOverviewLink();
        return <<<HTML
<h1>Realblog &ndash; {$this->title}</h1>
$message
<form name="confirm" method="post" action="$url">
    $hiddenFields
    <p style="text-align: center">$statusSelect $confirmationButton</p>
    <p>$overviewLink</p>
</form>
HTML;
    }

    /**
     * @return string
     * @global array $plugin_tx
     */
    private function renderStatusSelect()
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
