<!-- realblog most popular -->
<div class="realblog_most_popular">
    <p class="realbloglink"><?=$this->text('most_popular')?></p>
<?php if (!empty($this->articles)):?>
<?php   foreach ($this->articles as $article):?>
    <p>
        <a href="<?=$this->url($article)?>"><?=$this->escape($article->title)?></a>
        <span><?=$this->plural('page_views', $article->page_views)?></span>
    </p>
<?php   endforeach?>
<?php else:?>
    <p><?=$this->text('no_topics')?></p>
<?php endif?>
</div>
