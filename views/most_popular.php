<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $heading
 * @var list<array{id:int,title:string,page_views:int,url:string}> $articles
 */
?>
<!-- realblog most popular -->
<div class="realblog_most_popular">
  <<?=$this->esc($heading)?>><?=$this->text('most_popular')?></<?=$this->esc($heading)?>>
<?if (!empty($articles)):?>
<?  foreach ($articles as $article):?>
  <p>
    <a href="<?=$this->esc($article['url'])?>"><?=$this->esc($article['title'])?></a>
    <span><?=$this->plural('page_views', $article['page_views'])?></span>
  </p>
<?  endforeach?>
<?else:?>
  <p><?=$this->text('no_topics')?></p>
<?endif?>
</div>
