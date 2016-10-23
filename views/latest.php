<!-- realblog latest -->
<p class="realbloglink"><?=$this->text('links_visible_text')?></p>
<?php if (!empty($this->articles)):?>
<div class="realblog_tpl_show_box">
<?php   foreach ($this->articles as $article):?>
    <p class="realblog_tpl_show_date"><?=$this->formatDate($article)?></p>
    <p class="realblog_tpl_show_title">
        <a href="<?=$this->url($article)?>"><?=$this->escape($article->title)?></a>
    </p>
<?php       if ($this->showTeaser):?>
    <div class="realblog_tpl_show_story">
        <?=$this->teaser($article)?>
    </div>
    <p class="realblog_tpl_read_more">
        <a href="<?=$this->url($article)?>"><?=$this->text('read_more')?></a>
    </p>
<?php       endif?>
<?php   endforeach?>
</div>  
<?php else:?>
<p><?=$this->text('no_topics')?></p>
<?php endif?>
