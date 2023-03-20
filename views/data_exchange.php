<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var int $article_count
 * @var string $csrf_token
 * @var string $url
 * @var string|null $filename
 * @var string|null $filemtime
 * @var string $confirm_import
 */
?>
<!-- realblog data exchange -->
<div class="realblog_data_exchange">
  <h1>Realblog – <?=$this->text('exchange_heading')?></h1>
  <p>
    <span><?=$this->plural('exchange_count', $article_count)?></span>
  </p>
  <p>
<?if (isset($filename, $filemtime)):?>
    <span><?=$this->text('exchange_file_found', $filename, $filemtime)?></span>
<?else:?>
    <span><?=$this->text('exchange_file_notfound')?></span>
<?endif?>
  </p>
  <form action="<?=$url?>" method="post">
    <input type="hidden" name="admin" value="data_exchange">
    <input type="hidden" name="action" value="export_to_csv">
    <input type="hidden" name="xh_csrf_token" value="<?=$csrf_token?>">
    <button><?=$this->text('exchange_button_export')?></button>
  </form>
<?if (isset($filename)):?>
  <form id="realblog_import_csv" action="<?=$url?>" method="post" data-realblog='<?=$confirm_import?>'>
    <input type="hidden" name="admin" value="data_exchange">
    <input type="hidden" name="action" value="import_from_csv">
    <input type="hidden" name="xh_csrf_token" value="<?=$csrf_token?>">
    <button><?=$this->text('exchange_button_import')?></button>
  </form>
<?endif?>
</div>
