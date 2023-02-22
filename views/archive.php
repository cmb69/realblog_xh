<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var bool $isSearch
 * @var list<list<array{title:string,date:string,url:string,year:string,month:int}>> $articles
 * @var string $heading
 * @var int $year
 * @var string|null $backUrl
 * @var string|null $nextUrl
 */
?>
<!-- realblog archive -->
<div class="realblog_archive_container">

<?if (!$isSearch):?>

  <div class="realblog_table_paging">
<?  if (isset($backUrl)):?>
    <a href="<?=$this->esc($backUrl)?>" title="<?=$this->text('tooltip_previousyear')?>">◀</a>
<?  endif?>
    <span class="realblog_archive_title"><?=$this->text('archive_year')?> <?=$this->esc($year)?></span>
<?  if (isset($nextUrl)):?>
    <a href="<?=$this->esc($nextUrl)?>" title="<?=$this->text('tooltip_nextyear')?>">▶</a>
<?  endif?>
  </div>

<?endif?>

<?if (!empty($articles)):?>
<?  foreach ($articles as $group):?>
  <<?=$this->esc($heading)?>><?=$this->month($group[0]['month'])?> <?=$this->esc($group[0]['year'])?></<?=$this->esc($heading)?>>
  <ul class="realblog_archive">
<?    foreach ($group as $article):?>
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
