<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var list<int> $ids
 * @var string $action
 * @var string $csrfToken
 * @var list<string> $states
 * @var string $url
 */
?>
<!-- realblog confirm change status -->
<div class="realblog_confirm_change_status">
  <h1>Realblog – <?=$this->text('tooltip_change_status')?></h1>
<?if (count($ids)):?>
  <p class="xh_warning"><?=$this->text('confirm_changestatus')?></p>
  <form name="confirm" method="post" action="<?=$this->esc($action)?>">
<?foreach ($ids as $id):?>
    <input type="hidden" name="realblog_ids[]" value="<?=$this->esc($id)?>">
<?endforeach?>
    <input type="hidden" name="action" value="do_change_status"?>
    <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrfToken)?>">
    <p style="text-align: center">
      <select name="realblog_status">
      <option value="<?=$this->esc(-1)?>"><?=$this->text('new_realblogstatus')?></option>
<?foreach ($states as $i => $state):?>
        <option value="<?=$this->esc($i)?>"><?=$this->text($state)?></option>
<?endforeach?>
      </select>
      <input type="submit" name="submit" value="<?=$this->text('btn_ok')?>">
    </p>
  </form>
<?else:?>
  <p class="xh_info"><?=$this->text('nothing_selected')?></p>
<?endif?>
  <p><a href="<?=$this->esc($url)?>"><?=$this->text('blog_back')?></a></p>
</div>
