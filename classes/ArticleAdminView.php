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

use stdClass;

class ArticleAdminView
{
    /**
     * @var stdClass
     */
    protected $article;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $imageFolder;

    /**
     * @param stdClass $article
     * @param string $action
     * @global array $pth
     */
    public function __construct(stdClass $article, $action)
    {
        global $pth;

        $this->article = $article;
        $this->action = $action;
        $this->imageFolder = $pth['folder']['plugins'] . 'realblog/images/';
    }

    /**
     * @return string
     * @global array $pth
     * @global string $sn
     * @global array $plugin_tx
     * @global string $title
     * @global string $bjs
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
            . '<tr><td colspan="3"><span class="realblog_date_label">'
            . $plugin_tx['realblog']['label_categories']
            . '</span></td></tr>'
            . '<tr><td colspan="3">' . $this->renderCategories() . '<td></tr>'
            . '</table>'
            . '<h4>' . $plugin_tx['realblog']['title_label'] . '</h4>'
            . tag(
                'input type="text" value="' . $this->article->title
                . '" name="realblog_title" size="70"'
            )
            . $this->renderHeadline() . $this->renderStory()
            . $this->renderSubmitButton() . '</form>' . '</div>';
    }

    /**
     * @return string
     * @global \XH_CSRFProtection $_XH_csrfProtection
     */
    private function renderHiddenFields()
    {
        global $_XH_csrfProtection;

        $html = '';
        $fields = array(
            'realblog_id' => $this->article->id,
            'action' => 'do_' . $this->getVerb()
        );
        foreach ($fields as $name => $value) {
            $html .= $this->renderHiddenField($name, $value);
        }
        $html .= $_XH_csrfProtection->tokenInput();
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
     * @global array $plugin_tx
     */
    private function renderDate()
    {
        global $plugin_tx;

        $html = tag(
            'input type="date" name="realblog_date" id="date1" required="required"'
            . ' value="' . date('Y-m-d', $this->article->date) . '"'
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
     * @return string
     * @global array $plugin_cf
     * @global array $plugin_tx
     */
    private function renderPublishingDate()
    {
        global $plugin_cf, $plugin_tx;

        if ($plugin_cf['realblog']['auto_publish']) {
            $html = tag(
                'input type="date" name="realblog_startdate" id="date2"'
                . ' required="required" value="'
                . date('Y-m-d', $this->article->publishingDate) . '"'
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
     * @return string
     * @global array $plugin_cf
     * @global array $plugin_tx
     */
    private function renderArchiveDate()
    {
        global $plugin_cf, $plugin_tx;

        if ($plugin_cf['realblog']['auto_archive']) {
            $html = tag(
                'input type="date" name="realblog_enddate" id="date3"'
                . ' required="required" value="'
                . date('Y-m-d', $this->article->archivingDate) . '"'
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
     * @return string
     * @global array $plugin_tx
     */
    private function renderStatusSelect()
    {
        global $plugin_tx;

        $states = array('readyforpublishing', 'published', 'archived');
        $html = '<select name="realblog_status">';
        foreach ($states as $i => $state) {
            $selected = ($i == $this->article->status)
                ? 'selected="selected"' : '';
            $html .= '<option value="' . $i . '" ' . $selected . '>'
                . $plugin_tx['realblog'][$state] . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * @return string
     * @global array $plugin_tx
     */
    private function renderCommentsCheckbox()
    {
        global $plugin_tx;

        $checked = ($this->article->commentable)
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
     * @return string
     * @global array $plugin_tx
     */
    private function renderFeedCheckbox()
    {
        global $plugin_tx;

        $checked = ($this->article->feedable)
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
     * @return string
     */
    private function renderCategories()
    {
        return tag(
            'input type="text" size="70" name="realblog_categories" value="'
            . XH_hsc(trim($this->article->categories, ',')) . '"'
        );
    }

    /**
     * @return string
     * @global array $plugin_tx
     */
    private function renderHeadline()
    {
        global $plugin_tx;

        return '<h4>' . $plugin_tx['realblog']['headline_label'] . '</h4>'
            . '<textarea class="realblog_headline_field" name="realblog_headline"'
            . ' id="realblog_headline" rows="6" cols="60">'
            . XH_hsc($this->article->teaser) . '</textarea>';
    }

    /**
     * @return string
     * @global array $plugin_tx
     */
    private function renderStory()
    {
        global $plugin_tx;

        return '<h4>' . $plugin_tx['realblog']['story_label'] . '</h4>'
            . '<textarea class="realblog_story_field"'
            . ' name="realblog_story" id="realblog_story" rows="30" cols="80">'
            . XH_hsc($this->article->body) . '</textarea>';
    }

    /**
     * @return string
     * @global string $sn
     * @global array $plugin_tx
     */
    private function renderSubmitButton()
    {
        global $sn, $plugin_tx;

        return '<p style="text-align: center">'
            . tag(
                'input type="submit" name="save" value="'
                . $plugin_tx['realblog']['btn_' . $this->getVerb()] . '"'
            )
            . '</p>';
    }

    /**
     * @return string
     */
    private function getVerb()
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
