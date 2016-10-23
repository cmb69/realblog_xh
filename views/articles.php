<!-- realblog articles -->
<div class="realblog_show_box">
<?php if ($this->hasTopPagination):?>
    <?=$this->pagination->render()?>
<?php endif?>
    <div id="realblog_entries_preview" class="realblog_entries_preview">
<?php foreach ($this->articles as $article):?>
<?php   if ($this->hasMultiColumns):?>
        <div class="realblog_single_entry_preview">
            <div class="realblog_single_entry_preview_in">
<?php   endif?>
                <h4>
<?php   if ($this->hasLinkedHeader($article)):?>
                    <a href="<?=$this->url($article)?>" title="<?=$this->text('tooltip_view')?>">
<?php   endif?>
                    <?=$this->escape($article->title)?>
<?php   if ($this->hasLinkedHeader($article)):?>
                    </a>
<?php   endif?>
                </h4>
                <div class="realblog_show_date"><?=$this->date($article)?></div>
                <div class="realblog_show_story"><?=$this->teaser($article)?></div>
<?php   if ($this->hasReadMore($article)):?>
                <div class="realblog_entry_footer">
<?php       if ($this->isCommentable($article)):?>
                    <p class="realblog_number_of_comments">
                        <?=$this->plural('message_comments', $this->commentCount($article))?>
                    </p>
<?php       endif?>
                    <p class="realblog_read_more">
                        <a href="<?=$this->url($article)?>" title="<?=$this->text('tooltip_view')?>"><?=$this->text('read_more')?></a>
                    </p>
                </div>
<?php   endif?>
<?php   if ($this->hasMultiColumns):?>
            </div>
        </div>
<?php   endif?>
<?php endforeach?>
    </div>
<?php if ($this->hasBottomPagination):?>
    <?=$this->pagination->render()?>
<?php endif?>
</div>
