<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $pageUrl
 */
?>
<!-- realblog search form -->
<form class="realblog_search_form" method="get" action="<?=$actionUrl?>">
  <input type="hidden" name="selected" value="<?=$pageUrl?>">
  <input type="text" name="realblog_search" class="realblog_search_input"
       title="<?=$this->text('search_hint')?>" placeholder="<?=$this->text('search_placeholder')?>">
  <button><?=$this->text('search_button')?></button>
</form>
