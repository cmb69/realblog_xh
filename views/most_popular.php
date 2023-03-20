<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $heading
 * @var list<array{id:int,title:string,page_views:int,url:string}> $articles
 */
?>
<!-- realblog most popular -->
<div class="realblog_most_popular">
  <<?=$heading?>><?=$this->text('most_popular')?></<?=$heading?>>
<?if (!empty($articles)):?>
<?  foreach ($articles as $article):?>
  <p>
    <a href="<?=$article['url']?>"><?=$article['title']?></a>
    <span><?=$this->plural('page_views', $article['page_views'])?></span>
  </p>
<?  endforeach?>
<?else:?>
  <p><?=$this->text('no_topics')?></p>
<?endif?>
</div>
