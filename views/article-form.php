<!-- realblog article form -->
<div class="realblog_fields_block">
    <h1>Realblog – <?=$this->title?></h1>
    <form name="realblog" method="post" action="<?=$this->actionUrl?>">
        <input type="hidden" name="action" value="<?=$this->action?>">
        <input type="hidden" name="realblog_id" value="<?=$this->escape($this->article->id)?>">
        <input type="hidden" name="realblog_version" value="<?=$this->escape($this->article->version)?>">
        <input type="hidden" name="xh_csrf_token" value="<?=$this->csrfToken?>">
        <table>
            <tr>
                <td><label for="date1" class="realblog_label"><?=$this->text('date_label')?></label></td>
                <td><label for="date2" class="realblog_label"><?=$this->text('startdate_label')?></label></td>
                <td><label for="date3" class="realblog_label"><?=$this->text('enddate_label')?></span></label>
            </tr>
            <tr>
                <td>
<?php if ($this->article->id === 0):?>
                    <input type="hidden" name="realblog_date_exact" value="<?=$this->escape($this->article->date)?>">
                    <input type="hidden" name="realblog_date_old" value="<?=$this->formatDate($this->article->date)?>">
<?php endif?>
                    <input type="date" name="realblog_date" id="realblog_date1" required="required" value="<?=$this->formatDate($this->article->date)?>">
                    <img src="<?=$this->calendarIcon?>" id="realblog_trig_date1" class="realblog_date_selector" title="<?=$this->text('tooltip_datepicker')?>" alt="">
                </td>
                <td>
<?php if ($this->isAutoPublish):?>
                    <input type="date" name="realblog_startdate" id="realblog_date2" required="required" value="<?=$this->formatDate($this->article->publishingDate)?>">
                    <img src="<?=$this->calendarIcon?>" id="realblog_trig_date2" class="realblog_date_selector" title="<?=$this->text('tooltip_datepicker')?>" alt="">
<?php else:?>
                    <span><?=$this->text('startdate_hint')?></span>
                    <input type="hidden" name="realblog_startdate" value="<?=$this->formatDate($this->article->publishingDate)?>">
<?php endif?>
                </td>
                <td>
<?php if ($this->isAutoArchive):?>
                    <input type="date" name="realblog_enddate" id="realblog_date3" required="required" value="<?=$this->formatDate($this->article->archivingDate)?>">
                    <img src="<?=$this->calendarIcon?>" id="realblog_trig_date3" class="realblog_date_selector" title="<?=$this->text('tooltip_datepicker')?>" alt="">
<?php else:?>
                    <span><?=$this->text('enddate_hint')?></span>
                    <input type="hidden" name="realblog_enddate" value="<?=$this->formatDate($this->article->archivingDate)?>">
<?php endif?>
                </td>
            </tr>
            <tr>
                <td><label for="realblog_status" class="realblog_label"><?=$this->text('label_status')?></label></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>
                    <select id="realblog_status" name="realblog_status">
<?php foreach ($this->states as $i => $state):?>
                        <option value="<?=$this->escape($i)?>" <?php if ($this->article->status === $i) echo 'selected'?>><?=$this->text($state)?></option>
<?php endforeach?>
                    </select>
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="realblog_comments" <?php if ($this->article->commentable) echo 'checked'?>>
                        <span><?=$this->text('comment_label')?></span>
                    </label>
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="realblog_rssfeed" <?php if ($this->article->feedable) echo 'checked'?>>
                        <span><?=$this->text('label_rss')?></span>
                    </label>
                </td>
            </tr>
        </table>
        <p>
            <label for="realblog_categories" class="realblog_label"><?=$this->text('label_categories')?></label>
            <input type="text" id="realblog_categories" name="realblog_categories" value="<?=$this->categories?>" size="50">
            <select id="realblog_category_select">
                <option><?=$this->text('label_category_add')?></option>
            </select>
        </p>
        <p>
            <label for="realblog_title" class="realblog_label"><?=$this->text('title_label')?></label>
            <input type="text" id="realblog_title" name="realblog_title" value="<?=$this->escape($this->article->title)?>" size="50">
        </p>
        <p>
            <label for="realblog_headline" class="realblog_label"><?=$this->text('headline_label')?></label>
            <textarea class="realblog_headline_field" id="realblog_headline" name="realblog_headline" rows="6" cols="60"><?=$this->escape($this->article->teaser)?></textarea>
        </p>
        <p>
            <label for="realblog_story" class="realblog_label"><?=$this->text('story_label')?></label>
            <textarea class="realblog_story_field" id="realblog_story" name="realblog_story" rows="30" cols="80"><?=$this->escape($this->article->body)?></textarea>
        </p>
        <p style="text-align: center"><input type="submit" name="save" value="<?=$this->text($this->button)?>"></p>
    </form>
</div>
