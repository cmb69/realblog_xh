<!-- realblog article -->
<div class="realblog_article">

    <div class="realblog_show_box">
    
        <div class="realblog_buttons">
<?php if (isset($this->backToSearchUrl)):?>
            <a class="realblog_button" href="<?=$this->backToSearchUrl?>"><?=$this->text('search_back')?></a>
<?php endif?>
            <a class="realblog_button" href="<?=$this->backUrl?>"><?=$this->backText?></a>
<?php if ($this->isAdmin):?>
<?php   if ($this->wantsComments && isset($this->editCommentsUrl)):?>
            <a class="realblog_button" href="<?=$this->editCommentsUrl?>"><?=$this->text('comment_edit')?></a>
<?php   endif?>
            <a class="realblog_button" href="<?=$this->editUrl?>"><?=$this->text('entry_edit')?></a>
<?php endif?>
        </div>

<?php if (!$this->isHeadingAboveMeta):?>
        <<?=$this->heading?>><?=$this->escape($this->article->title)?></<?=$this->heading?>>
<?php endif?>
        <div class="realblog_article_meta">
            <span class="realblog_meta_date"><?=$this->text('message_published_on', $this->date)?></span>
<?php if ($this->categories):?>
            <span class="realblog_meta_categories"><?=$this->text('message_filed_under', $this->categories)?></span>
<?php endif?>
<?php if (isset($this->commentCount)):?>
            <span class="realblog_meta_comments"><?=$this->plural('message_comments', $this->commentCount)?></span>
<?php endif?>
        </div>
<?php if ($this->isHeadingAboveMeta):?>
        <<?=$this->heading?>><?=$this->escape($this->article->title)?></<?=$this->heading?>>
<?php endif?>
        <div class="realblog_show_story_entry"><?=$this->story?></div>
    
        <div class="realblog_buttons">
<?php if (isset($this->backToSearchUrl)):?>
            <a class="realblog_button" href="<?=$this->backToSearchUrl?>"><?=$this->text('search_back')?></a>
<?php endif?>
            <a class="realblog_button" href="<?=$this->backUrl?>"><?=$this->backText?></a>
<?php if ($this->isAdmin):?>
<?php   if ($this->wantsComments && isset($this->editCommentsUrl)):?>
            <a class="realblog_button" href="<?=$this->editCommentsUrl?>"><?=$this->text('comment_edit')?></a>
<?php   endif?>
            <a class="realblog_button" href="<?=$this->editUrl?>"><?=$this->text('entry_edit')?></a>
<?php endif?>
        </div>
    
    </div>

<?php if ($this->wantsComments):?>
    <?=$this->renderComments($this->article)?>
<?php endif?>

</div>
