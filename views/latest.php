<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $heading
 * @var list<array{title:string,date:string,url:string,teaser:string}> $articles
 * @var bool $show_teaser
 */
?>
<!-- realblog latest -->
<div class="realblog_latest">
  <<?=$heading?>><?=$this->text('links_visible_text')?></<?=$heading?>>
<?if (!empty($articles)):?>
  <div class="realblog_tpl_show_box">
<?  foreach ($articles as $article):?>
    <p class="realblog_tpl_show_date"><?=$article['date']?></p>
    <p class="realblog_tpl_show_title">
      <a href="<?=$article['url']?>"><?=$article['title']?></a>
    </p>
<?      if ($show_teaser):?>
    <div class="realblog_tpl_show_story"><?=$article['teaser']?></div>
    <p class="realblog_tpl_read_more">
      <a class="realblog_button" href="<?=$article['url']?>"><?=$this->text('read_more')?></a>
    </p>
<?      endif?>
<?  endforeach?>
  </div>  
<?else:?>
  <p><?=$this->text('no_topics')?></p>
<?endif?>
</div>
