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

<?php if (!empty($this->articles)):?>
<?php   foreach ($this->articles as $group):?>
    <<?=$this->heading?>><?=$this->monthName($this->monthOf($group[0]))?> <?=$this->escape($this->yearOf($group[0]))?></<?=$this->heading?>>
    <ul class="realblog_archive">
<?php       foreach ($group as $article):?>
        <li>
            <?=$this->formatDate($article)?>
            <a href="<?=$this->url($article)?>" title="<?=$this->text('tooltip_view')?>"><?=$this->escape($article->title)?></a>
        </li>
<?php       endforeach?>
    </ul>
<?php   endforeach?>
<?php else:?>
    <p><?=$this->text('no_topics')?></p>
<?php endif?>

</div>
