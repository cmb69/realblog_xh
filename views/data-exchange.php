<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var int $articleCount
 * @var string $csrfToken
 * @var string $url
 * @var string|null $filename
 * @var string|null $filemtime
 * @var string $confirmImport
 */
?>
<!-- realblog data exchange -->
<div class="realblog_data_exchange">
  <h1>Realblog – <?=$this->text('exchange_heading')?></h1>
  <p>
    <span><?=$this->plural('exchange_count', $articleCount)?></span>
  </p>
  <p>
<?if (isset($filename, $filemtime)):?>
    <span><?=$this->text('exchange_file_found', $filename, $filemtime)?></span>
<?else:?>
    <span><?=$this->text('exchange_file_notfound')?></span>
<?endif?>
  </p>
  <form action="<?=$this->esc($url)?>" method="post">
    <input type="hidden" name="admin" value="data_exchange">
    <input type="hidden" name="action" value="export_to_csv">
    <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrfToken)?>">
    <button><?=$this->text('exchange_button_export')?></button>
  </form>
<?if (isset($filename)):?>
  <form action="<?=$this->esc($url)?>" method="post" onsubmit="return confirm(<?=$this->esc($confirmImport)?>)">
    <input type="hidden" name="admin" value="data_exchange">
    <input type="hidden" name="action" value="import_from_csv">
    <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrfToken)?>">
    <button><?=$this->text('exchange_button_import')?></button>
  </form>
<?endif?>
</div>
