<!-- realblog articles -->
<div class="realblog_show_box">
<?php if ($this->hasTopPagination):?>
    <?=$this->pagination->render()?>
<?php endif?>
    <div id="realblog_entries_preview" class="realblog_entries_preview">
<?php foreach ($this->articles as $article):?>
        <div class="realblog_entry_preview">
<?php if ($this->isHeadingAboveMeta):?>
            <div class="realblog_article_meta">
                <span class="realblog_meta_date"><?=$this->text('message_published_on', $this->date($article))?></span>
<?php   if ($this->categories($article)):?>
                <span class="realblog_meta_categories"><?=$this->text('message_filed_under', $this->categories($article))?></span>
<?php   endif?>
<?php   if ($this->isCommentable($article)):?>
                <span class="realblog_meta_comments"><?=$this->plural('message_comments', $this->commentCount($article))?></span>
<?php   endif?>
            </div>
<?php endif?>
            <<?=$this->heading?>>
<?php   if ($this->hasLinkedHeader($article)):?>
                <a href="<?=$this->url($article)?>" title="<?=$this->text('tooltip_view')?>">
<?php   endif?>
                <?=$this->escape($article->title)?>
<?php   if ($this->hasLinkedHeader($article)):?>
                </a>
<?php   endif?>
            </<?=$this->heading?>>
<?php if (!$this->isHeadingAboveMeta):?>
            <div class="realblog_article_meta">
                <span class="realblog_meta_date"><?=$this->text('message_published_on', $this->date($article))?></span>
<?php   if ($this->categories($article)):?>
                <span class="realblog_meta_categories"><?=$this->text('message_filed_under', $this->categories($article))?></span>
<?php   endif?>
<?php   if ($this->isCommentable($article)):?>
                <span class="realblog_meta_comments"><?=$this->plural('message_comments', $this->commentCount($article))?></span>
<?php   endif?>
            </div>
<?php endif?>
            <div class="realblog_show_story"><?=$this->teaser($article)?></div>
<?php   if ($this->hasReadMore($article)):?>
            <div class="realblog_entry_footer">
                <p class="realblog_read_more">
                    <a class="realblog_button" href="<?=$this->url($article)?>" title="<?=$this->text('tooltip_view')?>"><?=$this->text('read_more')?></a>
                </p>
            </div>
<?php   endif?>
        </div>
<?php endforeach?>
    </div>
<?php if ($this->hasBottomPagination):?>
    <?=$this->pagination->render()?>
<?php endif?>
</div>
