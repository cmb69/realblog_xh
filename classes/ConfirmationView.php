<?php

/**
 * The confirmation views.
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
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * The confirmation views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
abstract class Realblog_ConfirmationView
{
    /**
     * The articles.
     *
     * @var array
     */
    protected $articles;

    /**
     * The title of the page.
     *
     * @var string
     */
    protected $title;

    /**
     * The label of the OK button.
     *
     * @var string
     */
    protected $buttonLabel;

    /**
     * Initializes a new instance.
     *
     * @return void
     *
     * @global Realblog_Controller The plugin controller.
     */
    public function __construct()
    {
        global $_Realblog_controller;

        $this->articles = $_Realblog_controller->getPgParameter('realblogtopics');
    }

    /**
     * Renders the change status view.
     *
     * @return string (X)HTML.
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
     * Renders the confirmation.
     *
     * @return string (X)HTML.
     */
    abstract protected function renderConfirmation();

    /**
     * Renders the hidden fields.
     *
     * @param string $do A do verb.
     *
     * @return string (X)HTML.
     *
     * @global XH_CSRFProtection The CSRF protector.
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
     * Renders a hidden field.
     *
     * @param string $name  A field name.
     * @param string $value A field value.
     *
     * @return string (X)HTML.
     */
    protected function renderHiddenField($name, $value)
    {
        return tag(
            'input type="hidden" name="' . $name . '" value="' . $value . '"'
        );
    }

    /**
     * Renders the confirmation buttons
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
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
     * Renders the no selection information.
     *
     * @return string (X)HTML.
     *
     * @global string              The script name.
     * @global array               The localization of the plugins.
     * @global Realblog_Controller The plugin controller.
     */
    protected function renderNoSelectionInfo()
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

?>
