<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var list<int> $ids
 * @var string $url
 * @var string $csrfToken
 * @var list<array{string}> $errors
 */
?>
<!-- realblog confirm delete -->
<div class="realblog_confirm_delete">
  <h1>Realblog – <?=$this->text('tooltip_delete_selected')?></h1>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
<?if (count($ids)):?>
  <p class="xh_warning"><?=$this->text('confirm_deleteall')?></p>
  <form name="confirm" method="post">
    <input type="hidden" name="xh_csrf_token" value="<?=$csrfToken?>">
    <p style="text-align: center">
      <button name="realblog_do"><?=$this->text('btn_delete')?></button>
    </p>
  </form>
<?else:?>
  <p class="xh_info"><?=$this->text('nothing_selected')?></p>
<?endif?>
  <p><a href="<?=$url?>"><?=$this->text('blog_back')?></a></p>
</div>
