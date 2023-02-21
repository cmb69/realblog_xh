<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $heading
 * @var list<array{id:int,title:string,pageViews:int,url:string}> $articles
 */
?>
<!-- realblog most popular -->
<div class="realblog_most_popular">
  <<?=$this->esc($heading)?>><?=$this->text('most_popular')?></<?=$this->esc($heading)?>>
<?if (!empty($articles)):?>
<?  foreach ($articles as $article):?>
  <p>
    <a href="<?=$this->esc($article['url'])?>"><?=$this->esc($article['title'])?></a>
    <span><?=$this->plural('page_views', $article['pageViews'])?></span>
  </p>
<?  endforeach?>
<?else:?>
  <p><?=$this->text('no_topics')?></p>
<?endif?>
</div>
