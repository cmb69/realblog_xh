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

abstract class ConfirmationView
{
    /**
     * @var array
     */
    protected $articles;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $buttonLabel;

    /**
     * @global Controller $_Realblog_controller
     */
    public function __construct()
    {
        global $_Realblog_controller;

        $this->articles = $_Realblog_controller->getPgParameter('realblogtopics');
    }

    /**
     * @return string
     */
    public function render()
    {
        if (count($this->articles) > 0) {
            $html = $this->renderConfirmation();
        } else {
            $html = $this->renderNoSelectionInfo();
        }
        return $html;
    }

    /**
     * @return string
     */
    abstract protected function renderConfirmation();

    /**
     * @param string $do
     * @return string
     * @global XH_CSRFProtection $_XH_csrfProtection
     */
    protected function renderHiddenFields($do)
    {
        global $_XH_csrfProtection;

        $html = '';
        foreach ($this->articles as $value) {
            $html .= $this->renderHiddenField('realblogtopics[]', $value);
        }
        $html .= $this->renderHiddenField('action', $do)
            . $_XH_csrfProtection->tokenInput();
        return $html;
    }

    /**
     * @param string $name
     * @param string $value
     * @return string
     */
    private function renderHiddenField($name, $value)
    {
        return tag(
            'input type="hidden" name="' . $name . '" value="' . $value . '"'
        );
    }

    /**
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    protected function renderConfirmationButtons()
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        $html = tag(
            'input type="submit" name="submit" value="'
            . $this->buttonLabel . '"'
        );
        $html .= '&nbsp;&nbsp;';
        $url = $sn . '?&amp;realblog&amp;admin=plugin_main&amp;action=plugin_text'
            . '&amp;page=' . $_Realblog_controller->getPage();
        $html .= tag(
            'input type="button" name="cancel" value="'
            . $plugin_tx['realblog']['btn_cancel'] . '" onclick="'
            . 'location.href=&quot;' . $url . '&quot;"'
        );
        return $html;
    }

    /**
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    private function renderNoSelectionInfo()
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        return '<h1>Realblog &ndash; ' . $this->title . '</h1>'
            . '<form name="confirm" method="post" action="' . $sn
            . '?&amp;' . 'realblog' . '&amp;admin=plugin_main">'
            . '<table width="100%">'
            . '<tr><td class="realblog_confirm_info" align="center">'
            . $plugin_tx['realblog']['nothing_selected']
            . '</td></tr>'
            . '<tr><td class="realblog_confirm_button" align="center">'
            . tag(
                'input type="button" name="cancel" value="'
                . $plugin_tx['realblog']['btn_ok'] . '" onclick=\''
                . 'location.href="' . $sn . '?&amp;realblog'
                . '&amp;admin=plugin_main&amp;action=plugin_text'
                . '&amp;page=' . $_Realblog_controller->getPage() . '"\''
            )
            . '</td></tr>'
            . '</table></form>';
    }
}
