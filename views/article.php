<!-- realblog article -->
<div class="realblog_show_box">

    <div class="realblog_buttons">
        <span class="realblog_button"><a href="<?=$this->backUrl?>"><?=$this->backText?></a></span>
<?php if ($this->isAdmin):?>
<?php   if ($this->wantsComments):?>
        <span class="realblog_button"><a href="<?=$this->editCommentsUrl?>"><?=$this->text('comment_edit')?></a></span>
<?php   endif?>
        <span class="realblog_button"><a href="<?=$this->editUrl?>"><?=$this->text('entry_edit')?></a></span>
<?php endif?>
        <div style="clear: both"></div>
    </div>

    <h4><?=$this->escape($this->article->title)?></h4>
    <div class="realblog_show_date"><?=$this->date?></div>
    <div class="realblog_show_story_entry"><?=$this->story?></div>

    <div class="realblog_buttons">
        <span class="realblog_button"><a href="<?=$this->backUrl?>"><?=$this->backText?></a></span>
<?php if ($this->isAdmin):?>
<?php   if ($this->wantsComments):?>
        <span class="realblog_button"><a href="<?=$this->editCommentsUrl?>"><?=$this->text('comment_edit')?></a></span>
<?php   endif?>
        <span class="realblog_button"><a href="<?=$this->editUrl?>"><?=$this->text('entry_edit')?></a></span>
<?php endif?>
        <div style="clear: both"></div>
    </div>

</div>

<?php if ($this->wantsComments):?>
<?=$this->renderComments($this->article)?>
<?php endif?>
