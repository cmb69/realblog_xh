<!-- realblog confirm change status -->
<h1>Realblog – <?=$this->text('tooltip_changestatus')?></h1>
<?php if (count($this->ids)):?>
<p class="xh_warning"><?=$this->text('confirm_changestatus')?></p>
<form name="confirm" method="post" action="<?=$this->action?>">
<?php foreach ($this->ids as $id):?>
    <input type="hidden" name="realblog_ids[]" value="<?=$this->escape($id)?>">
<?php endforeach?>
    <input type="hidden" name="action" value="do_batchchangestatus"?>
    <?=$this->csrfTokenInput?>
    <p style="text-align: center">
        <select name="realblog_status">
<?php foreach ($this->states as $i => $state):?>
            <option value="<?=$this->escape($i - 1)?>"><?=$this->text($state)?></option>
<?php endforeach?>
        </select>
        <input type="submit" name="submit" value="<?=$this->text('btn_ok')?>">
    </p>
</form>
<?php else:?>
<p class="xh_info"><?=$this->text('nothing_selected')?></p>
<?php endif?>
<p><a href="<?=$this->url?>"><?=$this->text('blog_back')?></a></p>
