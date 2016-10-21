<!-- realblog article form -->
<div class="realblog_fields_block">
    <h1>Realblog – <?=$this->title?></h1>
    <form name="realblog" method="post" action="<?=$this->actionUrl?>">
        <input type="hidden" name="realblog_id" value="<?=$this->escape($this->article->id)?>">
        <input type="hidden" name="action" value="<?=$this->action?>">
        <?=$this->tokenInput?>
        <table>
            <tr>
                <td><span class="realblog_date_label"><?=$this->text('date_label')?></span></td>
                <td><span class="realblog_date_label"><?=$this->text('startdate_label')?></span></td>
                <td><span class="realblog_date_label"><?=$this->text('enddate_label')?></span></td>
            </tr>
            <tr>
                <td>
                    <input type="date" name="realblog_date" id="date1" required="required" value="<?=$this->formatDate($this->article->date)?>">
                    <img src="<?=$this->calendarIcon?>" id="trig_date1" class="realblog_date_selector" title="<?=$this->text('tooltip_datepicker')?>" alt="">
                </td>
                <td>
<?php if ($this->isAutoPublish):?>
                    <input type="date" name="realblog_startdate" id="date2" required="required" value="<?=$this->formatDate($this->article->publishing_date)?>">
                    <img src="<?=$this->calendarIcon?>" id="trig_date2" class="realblog_date_selector" title="<?=$this->text('tooltip_datepicker')?>" alt="">
<?php else:?>
                    <?=$this->text('startdate_hint')?>
<?php endif?>
                </td>
                <td>
<?php if ($this->isAutoArchive):?>
                    <input type="date" name="realblog_enddate" id="date3" required="required" value="<?=$this->formatDate($this->article->archivingDate)?>">
                    <img src="<?=$this->calendarIcon?>" id="trig_date3" class="realblog_date_selector" title="<?=$this->text('tooltip_datepicker')?>" alt="">
<?php else:?>
                    <?=$this->text('enddate_hint')?>
<?php endif?>
                </td>
            </tr>
            <tr>
                <td><span class="realblog_date_label"><?=$this->text('label_status')?></span></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <select name="realblog_status">
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
            <tr>
                <td colspan="3"><span class="realblog_date_label"><?=$this->text('label_categories')?></span></td>
            </tr>
            <tr>
                <td colspan="3"><input type="text" size="70" name="realblog_categories" value="<?=$this->categories?>"><td>
            </tr>
        </table>
        <h4><?=$this->text('title_label')?></h4>
        <input type="text" value="<?=$this->escape($this->article->title)?>" name="realblog_title" size="70">
        <h4><?=$this->text('headline_label')?></h4>
        <textarea class="realblog_headline_field" name="realblog_headline" id="realblog_headline" rows="6" cols="60"><?=$this->escape($this->article->teaser)?></textarea>
        <h4><?=$this->text('story_label')?></h4>
        <textarea class="realblog_story_field" name="realblog_story" id="realblog_story" rows="30" cols="80"><?=$this->escape($this->article->body)?></textarea>
        <p style="text-align: center"><input type="submit" name="save" value="<?=$this->text($this->button)?>"></p>
    </form>
</div>    
