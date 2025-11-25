<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var int $id
 * @var string $url
 * @var string $csrfToken
 * @var list<array{string}> $errors
 */
?>
<!-- realblog confirm delete -->
<div class="realblog_confirm_delete">
  <h1>Realblog – <?=$this->text('title_delete', $id)?></h1>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <p class="xh_warning"><?=$this->text('confirm_delete')?></p>
  <form name="confirm" method="post">
    <input type="hidden" name="realblog_token" value="<?=$this->esc($csrfToken)?>">
    <p style="text-align: center">
      <button name="realblog_do"><?=$this->text('btn_delete')?></button>
    </p>
  </form>
  <p><a href="<?=$this->esc($url)?>"><?=$this->text('blog_back')?></a></p>
</div>
