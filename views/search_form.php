<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $selected
 * @var int|null $page
 * @var int|null $year
 */
?>
<!-- realblog search form -->
<form class="realblog_search_form" method="get">
  <input type="hidden" name="selected" value="<?=$selected?>">
<?if (isset($page)):?>
  <input type="hidden" name="realblog_page" value="<?=$page?>">
<?endif?>
<?if (isset($year)):?>
  <input type="hidden" name="realblog_year" value="<?=$year?>">
<?endif?>
  <input type="text" name="realblog_search" class="realblog_search_input"
       title="<?=$this->text('search_hint')?>" placeholder="<?=$this->text('search_placeholder')?>">
  <button><?=$this->text('search_button')?></button>
</form>
