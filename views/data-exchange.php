<!-- realblog data exchange -->
<div class="realblog_data_exchange">
    <h1>Realblog – <?=$this->text('exchange_heading')?></h1>
    <p>
        <span><?=$this->plural('exchange_count', $this->articleCount)?></span>
    </p>
    <p>
<?php if (isset($this->filename)):?>
        <span><?=$this->text('exchange_file_found', $this->filename, $this->filemtime)?></span>
<?php else:?>
        <span><?=$this->text('exchange_file_notfound')?></span>
<?php endif?>
    </p>
    <form action="<?=$this->url?>" method="post">
        <input type="hidden" name="admin" value="data_exchange">
        <input type="hidden" name="action" value="export_to_csv">
        <input type="hidden" name="xh_csrf_token" value="<?=$this->csrfToken?>">
        <button><?=$this->text('exchange_button_export')?></button>
    </form>
<?php if (isset($this->filename)):?>
    <form action="<?=$this->url?>" method="post" onsubmit="return confirm(<?=$this->confirmImport?>)">
        <input type="hidden" name="admin" value="data_exchange">
        <input type="hidden" name="action" value="import_from_csv">
        <input type="hidden" name="xh_csrf_token" value="<?=$this->csrfToken?>">
        <button><?=$this->text('exchange_button_import')?></button>
    </form>
<?php endif?>
</div>
