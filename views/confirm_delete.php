<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var list<int> $ids
 * @var string $action
 * @var string $url
 * @var string $csrfToken
 */
?>
<!-- realblog confirm delete -->
<div class="realblog_confirm_delete">
  <h1>Realblog – <?=$this->text('tooltip_delete_selected')?></h1>
<?if (count($ids)):?>
  <p class="xh_warning"><?=$this->text('confirm_deleteall')?></p>
  <form name="confirm" method="post" action="<?=$action?>">
<?foreach ($ids as $id):?>
    <input type="hidden" name="realblog_ids[]" value="<?=$id?>">
<?endforeach?>
    <input type="hidden" name="action" value="do_delete_selected"?>
    <input type="hidden" name="xh_csrf_token" value="<?=$csrfToken?>">
    <p style="text-align: center">
      <input type="submit" name="submit" value="<?=$this->text('btn_delete')?>">
    </p>
  </form>
<?else:?>
  <p class="xh_info"><?=$this->text('nothing_selected')?></p>
<?endif?>
  <p><a href="<?=$url?>"><?=$this->text('blog_back')?></a></p>
</div>
