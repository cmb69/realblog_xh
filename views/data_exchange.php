<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var int $article_count
 * @var string $filename
 * @var string|null $filemtime
 * @var string $script
 */
?>
<!-- realblog data exchange -->
<script type="module" src="<?=$this->esc($script)?>"></script>
<div class="realblog_data_exchange">
  <h1>Realblog – <?=$this->text('exchange_heading')?></h1>
  <form method="get">
    <input type="hidden" name="selected" value="realblog">
    <input type="hidden" name="admin" value="data_exchange">
    <fieldset>
      <legend><?=$this->text('exchange_button_export')?></legend>
      <p class="xh_info"><?=$this->plural('exchange_count', $article_count)?></p>
      <button name="action" value="export"><?=$this->text('exchange_button_export')?></button>
    </fieldset>
    <fieldset>
      <legend><?=$this->text('exchange_button_import')?></legend>
<?if (isset($filemtime)):?>
      <p class="xh_info"><?=$this->text('exchange_file_found', $filename, $filemtime)?></p>
<?else:?>
      <p class="xh_info"><?=$this->text('exchange_file_notfound')?></p>
<?endif?>
<?if (isset($filemtime)):?>
      <button name="action" value="import"><?=$this->text('exchange_button_import')?></button>
    </fieldset>
  </form>
<?endif?>
</div>
