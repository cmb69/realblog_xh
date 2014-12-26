<?php

/**
 * The article administration views.
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
 * The article administration views.
 *
 * @category CMSimple_XH
 * @package  Realblog
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Realblog_XH
 */
class Realblog_ArticleAdminView
{
    /**
     * The article record.
     *
     * @var Realblog_Article
     */
    protected $article;

    /**
     * The requested action.
     *
     * @var string
     */
    protected $action;

    /**
     * The paths of the plugin image folder.
     *
     * @var string
     */
    protected $imageFolder;

    /**
     * Initializes a new instance.
     *
     * @param Realblog_Article $article An article record.
     * @param string           $action  An action.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
    public function __construct(Realblog_Article $article, $action)
    {
        global $pth;

        $this->article = $article;
        $this->action = $action;
        $this->imageFolder = $pth['folder']['plugins'] . 'realblog/images/';
    }

    /**
     * Renders the article administration view.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global string The script name.
     * @global array  The localization of the plugins.
     * @global string The title of the page.
     * @global string The (X)HTML fragment to insert before the closing body tag.
     */
    public function render()
    {
        global $pth, $sn, $plugin_tx, $title, $bjs;

        $bjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
            . 'realblog/realblog.js"></script>';
        return '<div class="realblog_fields_block"><h1>Realblog &ndash; '
            . $title . '</h1>'
            . '<form name="realblog" method="post" action="' . $sn . '?&amp;'
            . 'realblog' . '&amp;admin=plugin_main">'
            . $this->renderHiddenFields()
            . '<table>'
            . '<tr><td><span class="realblog_date_label">'
            . $plugin_tx['realblog']['date_label'] . '</span></td>'
            . '<td><span class="realblog_date_label">'
            . $plugin_tx['realblog']['startdate_label'] . '</span></td>'
            . '<td><span class="realblog_date_label">'
            . $plugin_tx['realblog']['enddate_label'] . '</span></td></tr><tr>'
            . '<td>' . $this->renderDate() . '</td>'
            . '<td>' . $this->renderPublishingDate() . '</td>'
            . '<td>' . $this->renderArchiveDate() . '</td></tr><tr>'
            . '<td><span class="realblog_date_label">'
            . $plugin_tx['realblog']['label_status'] . '</span></td>'
            . '<td></td><td></td></tr><tr>'
            . '<td>' . $this->renderStatusSelect() . '</td>'
            . '<td>' . $this->renderCommentsCheckbox() . '</td>'
            . '<td>' . $this->renderFeedCheckbox() . '</td></tr>'
            . '</table>'
            . '<h4>' . $plugin_tx['realblog']['title_label'] . '</h4>'
            . tag(
                'input type="text" value="' . $this->article->getTitle()
                . '" name="realblog_title" size="70"'
            )
            . $this->renderHeadline() . $this->renderStory()
            . $this->renderSubmitButtons() . '</form>' . '</div>';
    }

    /**
     * Renders the hidden fields.
     *
     * @return string (X)HTML.
     *
     * @global XH_CSRFProtection The CSRF protector.
     */
    protected function renderHiddenFields()
    {
        global $_XH_csrfProtection;

        $html = '';
        $fields = array(
            'realblog_id' => $this->article->getId(),
            'action' => 'do_' . $this->getVerb()
        );
        foreach ($fields as $name => $value) {
            $html .= $this->renderHiddenField($name, $value);
        }
        $html .= $_XH_csrfProtection->tokenInput();
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
     * Renders the date input.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderDate()
    {
        global $plugin_tx;

        $html = tag(
            'input type="date" name="realblog_date" id="date1" required="required"'
            . ' value="' . date('Y-m-d', $this->article->getDate()) . '"'
        );
        $html .= '&nbsp;'
            . tag(
                'img src="' . $this->imageFolder . 'calendar.png"'
                . ' id="trig_date1" class="realblog_date_selector" title="'
                . $plugin_tx['realblog']['tooltip_datepicker'] . '" alt=""'
            );
        return $html;
    }

    /**
     * Renders the publishing date input.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    protected function renderPublishingDate()
    {
        global $plugin_cf, $plugin_tx;

        if ($plugin_cf['realblog']['auto_publish']) {
            $html = tag(
                'input type="date" name="realblog_startdate" id="date2"'
                . ' required="required" value="'
                . date('Y-m-d', $this->article->getPublishingDate()) . '"'
            );
            $html .= '&nbsp;'
                . tag(
                    'img src="' . $this->imageFolder . 'calendar.png"'
                    . ' id="trig_date2" class="realblog_date_selector" title="'
                    . $plugin_tx['realblog']['tooltip_datepicker'] . '" alt=""'
                );
        } else {
            $html = $plugin_tx['realblog']['startdate_hint'];
        }
        return $html;
    }

    /**
     * Renders the archiving date input.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    protected function renderArchiveDate()
    {
        global $plugin_cf, $plugin_tx;

        if ($plugin_cf['realblog']['auto_archive']) {
            $html = tag(
                'input type="date" name="realblog_enddate" id="date3"'
                . ' required="required" value="'
                . date('Y-m-d', $this->article->getArchivingDate()) . '"'
            );
            $html .= '&nbsp;'
                . tag(
                    'img src="' . $this->imageFolder . 'calendar.png"'
                    . ' id="trig_date3" class="realblog_date_selector" title="'
                    . $plugin_tx['realblog']['tooltip_datepicker'] . '" alt=""'
                );
        } else {
            $html = $plugin_tx['realblog']['enddate_hint'];
        }
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

        $states = array('readyforpublishing', 'published', 'archived', 'backuped');
        $html = '<select name="realblog_status">';
        foreach ($states as $i => $state) {
            $selected = ($i == $this->article->getStatus())
                ? 'selected="selected"' : '';
            $html .= '<option value="' . $i . '" ' . $selected . '>'
                . $plugin_tx['realblog'][$state] . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Renders the comments checkbox.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderCommentsCheckbox()
    {
        global $plugin_tx;

        $checked = ($this->article->isCommentable())
            ? 'checked="checked"' : '';
        return '<label>'
            . tag(
                'input type="checkbox" name="realblog_comments" '
                . $checked
            )
            . '&nbsp;<span>' . $plugin_tx['realblog']['comment_label']
            . '</span></label>';
    }

    /**
     * Renders the feed checkbox.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderFeedCheckbox()
    {
        global $plugin_tx;

        $checked = ($this->article->isFeedable())
            ? 'checked="checked"' : '';
        return '<label>'
            . tag(
                'input type="checkbox" name="realblog_rssfeed" '
                . $checked
            )
            . '&nbsp;<span>' . $plugin_tx['realblog']['label_rss']
            . '</span></label>';
    }

    /**
     * Renders the headline (teaser).
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderHeadline()
    {
        global $plugin_tx;

        return '<h4>' . $plugin_tx['realblog']['headline_label'] . '</h4>'
            . '<p>' . $plugin_tx['realblog']['label_template']
            . tag(
                'input type="text" value="{{{rbCat(\'|category1|category2|\');}}}"'
                . ' readonly="readonly" onclick="this.select()"'
                . ' style="margin-left: 0.5em"'
            )
            . '</p>'
            . '<textarea class="realblog_headline_field" name="realblog_headline"'
            . ' id="realblog_headline" rows="6" cols="60">'
            . XH_hsc($this->article->getTeaser()) . '</textarea>';
    }

    /**
     * Renders the story (body).
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    protected function renderStory()
    {
        global $plugin_tx;

        return '<h4>' . $plugin_tx['realblog']['story_label'] . '</h4>'
            . '<textarea class="realblog_story_field"'
            . ' name="realblog_story" id="realblog_story" rows="30" cols="80">'
            . XH_hsc($this->article->getBody()) . '</textarea>';
    }

    /**
     * Renders the submit buttons.
     *
     * @return string (X)HTML.
     *
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function renderSubmitButtons()
    {
        global $sn, $plugin_tx;

        return '<p style="text-align: center">'
            . tag(
                'input type="submit" name="save" value="'
                . $plugin_tx['realblog']['btn_' . $this->getVerb()] . '"'
            )
            . '&nbsp;&nbsp;&nbsp;'
            . tag(
                'input type="button" name="cancel" value="'
                . $plugin_tx['realblog']['btn_cancel'] . '" onclick="'
                . 'location.href=&quot;' . $sn . '?&amp;realblog&amp;'
                . 'admin=plugin_main&amp;action=plugin_text' . '&quot;"'
            )
            . '</p>';
    }

    /**
     * Gets the current verb.
     *
     * @return string
     */
    protected function getVerb()
    {
        switch ($this->action) {
        case 'add_realblog':
            return 'add';
        case 'modify_realblog':
            return 'modify';
        case 'delete_realblog':
            return 'delete';
        }
    }
}

?>
