<?php

use Plib\View;

/**
 * @var View $this
 * @var int $article_count
 * @var string $csrf_token
 * @var string $filename
 * @var bool $file_exists
 * @var list<array{string}> $errors
 */
?>
<!-- realblog data export -->
<div class="realblog_data_exchange">
  <h1>Realblog – <?=$this->text('exchange_button_export')?></h1>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text('exchange_export_failure', $filename)?></p>
<?endforeach?>
  <p class="xh_info"><?=$this->plural('exchange_count', $article_count)?></p>
<?if ($file_exists):?>
  <p class="xh_warning"><?=$this->text('exchange_confirm_export', $filename)?></p>
<?endif?>
  <form method="post">
    <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrf_token)?>">
    <button name="realblog_do"><?=$this->text('exchange_button_export')?></button>
  </form>
</div>
