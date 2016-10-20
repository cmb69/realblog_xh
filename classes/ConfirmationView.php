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
     * @var array<int>
     */
    protected $ids;

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

        $this->ids = $_Realblog_controller->getPgParameter('realblogtopics');
    }

    /**
     * @return string
     */
    public function render()
    {
        if (count($this->ids) > 0) {
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
        foreach ($this->ids as $value) {
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
     */
    protected function renderConfirmationButton()
    {
        return tag(
            'input type="submit" name="submit" value="'
            . $this->buttonLabel . '"'
        );
    }

    /**
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     * @global Controller $_Realblog_controller
     */
    protected function renderOverviewLink()
    {
        global $sn, $plugin_tx, $_Realblog_controller;

        $page = $_Realblog_controller->getPage();
        $url = XH_hsc("$sn?&realblog&admin=plugin_main&action=plugin_text&page=$page");
        return <<<HTML
<a href="$url">{$plugin_tx['realblog']['blog_back']}</a>
HTML;
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

        $message = XH_message('info', $plugin_tx['realblog']['nothing_selected']);
        $page = $_Realblog_controller->getPage();
        $url = XH_hsc("$sn?&realblog&admin=plugin_main&action=plugin_text&page=$page");
        return <<<HTML
<h1>Realblog &ndash; {$this->title}</h1>
$message
<p><a href="$url">{$plugin_tx['realblog']['blog_back']}</a></p>
HTML;
    }
}
