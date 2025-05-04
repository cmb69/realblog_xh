<?php

use Plib\View;

/**
 * @var View $this
 * @var int $article_count
 * @var string $csrf_token
 * @var string $filename
 * @var string $filemtime
 * @var list<array{string}> $errors
 */
?>
<!-- realblog data import -->
<div class="realblog_data_exchange">
  <h1>Realblog – <?=$this->text('exchange_button_import')?></h1>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text('exchange_import_failure', $filename)?></p>
<?endforeach?>
  <p class="xh_info"><?=$this->plural('exchange_count', $article_count)?></p>
  <p class="xh_warning"><?=$this->text('exchange_confirm_import')?></p>
  <form method="post">
    <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrf_token)?>">
    <button name="realblog_do"><?=$this->text('exchange_button_import')?></button>
  </form>
</div>
