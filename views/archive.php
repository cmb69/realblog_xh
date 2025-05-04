<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var list<array{year:int,month:int,articles:list<array{title:string,date:string,url:string}>}> $articles
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
    <a class="realblog_button" href="<?=$year['url']?>"><?=$year['year']?></a>
<?  else:?>
    <span class="realblog_pag_current"><?=$year['year']?></span>
<?  endif?>
<?endforeach?>
  </div>
<?endif?>
<?if (!empty($articles)):?>
<?  foreach ($articles as $group):?>
  <<?=$heading?>><?=$group['month']?> <?=$group['year']?></<?=$heading?>>
  <ul class="realblog_archive">
<?    foreach ($group['articles'] as $article):?>
    <li>
      <?=$article['date']?>
      <a href="<?=$article['url']?>" title="<?=$this->text('tooltip_view')?>"><?=$article['title']?></a>
    </li>
<?    endforeach?>
  </ul>
<?  endforeach?>
<?else:?>
  <p><?=$this->text('no_topics')?></p>
<?endif?>
</div>
