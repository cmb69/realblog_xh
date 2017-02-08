<!-- realblog archive -->
<div class="realblog_archive_container">

<?php if (!$this->isSearch):?>

    <div class="realblog_table_paging">
<?php   if (isset($this->backUrl)):?>
        <a href="<?=$this->backUrl?>" title="<?=$this->text('tooltip_previousyear')?>">◀</a>
<?php   endif?>
        <span class="realblog_archive_title"><?=$this->text('archive_year')?> <?=$this->year?></span>
<?php   if (isset($this->nextUrl)):?>
        <a href="<?=$this->nextUrl?>" title="<?=$this->text('tooltip_nextyear')?>">▶</a>
<?php   endif?>
    </div>

<?php endif?>

<?php if (count($this->articles) > 0):?>
<?php   $first = true?>
<?php   $currentMonth = -1?>
<?php   $currentYear = -1?>
<?php   foreach ($this->articles as $article):?>
<?php       $month = $this->monthOf($article)?>
<?php       $year = $this->yearOf($article)?>
<?php       if ($month != $currentMonth || $year != $currentYear):?>
<?php           $currentMonth = $month?>
<?php           $currentYear = $year?>
<?php           if (!$first):?>
    </ul>
<?php           else:?>
<?php               $first = false?>
<?php           endif?>
<<?=$this->heading?>><?=$this->monthName($month)?> <?=$this->escape($year)?></<?=$this->heading?>>
    <ul class="realblog_archive">
<?php       endif?>
        <li>
            <?=$this->formatDate($article)?>
            <a href="<?=$this->url($article)?>" title="<?=$this->text('tooltip_view')?>"><?=$this->escape($article->title)?></a>
        </li>
<?php   endforeach?>
    </ul>
<?php else:?>
    <p><?=$this->text('no_topics')?></p>
<?php endif?>

</div>
