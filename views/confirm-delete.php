<!-- realblog confirm delete -->
<div class="realblog_confirm_delete">
    <h1>Realblog – <?=$this->text('tooltip_delete_selected')?></h1>
<?php if (count($this->ids)):?>
    <p class="xh_warning"><?=$this->text('confirm_deleteall')?></p>
    <form name="confirm" method="post" action="<?=$this->action?>">
<?php foreach ($this->ids as $id):?>
        <input type="hidden" name="realblog_ids[]" value="<?=$this->escape($id)?>">
<?php endforeach?>
        <input type="hidden" name="action" value="do_delete_selected"?>
        <input type="hidden" name="xh_csrf_token" value="<?=$this->csrfToken?>">
        <p style="text-align: center">
            <input type="submit" name="submit" value="<?=$this->text('btn_delete')?>">
        </p>
    </form>
<?php else:?>
    <p class="xh_info"><?=$this->text('nothing_selected')?></p>
<?php endif?>
    <p><a href="<?=$this->url?>"><?=$this->text('blog_back')?></a></p>
</div>
