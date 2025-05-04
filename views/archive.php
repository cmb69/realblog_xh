<?php

use Plib\View;

/**
 * @var View $this
 * @var list<array{year:int,month:string,articles:list<array{title:string,date:string,url:string}>}> $articles
 * @var string $heading
 * @var list<array{year:int,url:string|null}>|null $years
 */
?>
<!-- realblog archive -->
<div class="realblog_show_box">
<?if (isset($years)):?>
  <div class="realblog_pagination">
<?foreach ($years as $year):?>
<?  if (isset($year['url'])):?>
    <a class="realblog_button" href="<?=$this->esc($year['url'])?>"><?=$this->esc($year['year'])?></a>
<?  else:?>
    <span class="realblog_pag_current"><?=$this->esc($year['year'])?></span>
<?  endif?>
<?endforeach?>
  </div>
<?endif?>
<?if (!empty($articles)):?>
<?  foreach ($articles as $group):?>
  <<?=$this->esc($heading)?>><?=$this->esc($group['month'])?> <?=$this->esc($group['year'])?></<?=$this->esc($heading)?>>
  <ul class="realblog_archive">
<?    foreach ($group['articles'] as $article):?>
    <li>
      <?=$this->esc($article['date'])?>
      <a href="<?=$this->esc($article['url'])?>" title="<?=$this->text('tooltip_view')?>"><?=$this->esc($article['title'])?></a>
    </li>
<?    endforeach?>
  </ul>
<?  endforeach?>
<?else:?>
  <p><?=$this->text('no_topics')?></p>
<?endif?>
</div>
