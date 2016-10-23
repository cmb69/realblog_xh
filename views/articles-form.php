<!-- realblog articles form -->
<h1>Realblog – <?=$this->text('story_overview')?></h1>
<form class="realblog_filter" method="get" action="<?=$this->actionUrl?>">
    <input type="hidden" name="selected" value="realblog">
    <input type="hidden" name="admin" value="plugin_main">
    <input type="hidden" name="action" value="plugin_text">
<?php foreach ($this->states as $i => $status):?>
    <input type="hidden" name="realblog_filter<?=$i+1?>" value="">
    <label>
        <input type="checkbox" name="realblog_filter<?=$i+1?>" <?php if ($this->hasFilter($i+1)) echo 'checked'?>>
        <?=$this->text($status)?>
    </label>
<?php endforeach?>
    <button><?=$this->text('btn_filter')?></button>
</form>

<form method="get" action="<?=$this->actionUrl?>">
    <input type="hidden" name="selected" value="realblog">
    <input type="hidden" name="admin" value="plugin_main">
    <input type="hidden" name="action" value="plugin_text">
<?php if ($this->hasTopPagination):?>
    <?=$this->pagination->render()?>
<?php endif?>
    <table class="realblog_table">
        <tr>
            <td class="realblog_table_header">
                <button name="action" value="batchdelete" title="<?=$this->text('tooltip_deleteall')?>">
                    <img src="<?=$this->deleteIcon?>" alt="<?=$this->text('tooltip_deleteall')?>">
                </button>
            </td>
            <td class="realblog_table_header">
                <button name="action" value="change_status" title="<?=$this->text('tooltip_changestatus')?>">
                    <img src="<?=$this->changeStatusIcon?>" alt="<?=$this->text('tooltip_changestatus')?>">
                </button>
            </td>
            <td class="realblog_table_header">
                <a href="<?=$this->addUrl?>" title="<?=$this->text('tooltip_add')?>">
                    <img src="<?=$this->addIcon?>" alt="<?$this->text('tooltip_add')?>"
                </a>
            </td>
            <td class="realblog_table_header"><?=$this->text('id_label')?></td>
            <td class="realblog_table_header"><?=$this->text('date_label')?></td>
            <td class="realblog_table_header"><?=$this->text('label_status')?></td>
            <td class="realblog_table_header"><?=$this->text('label_rss')?></td>
            <td class="realblog_table_header"><?=$this->text('comments_onoff')?></td>
        </tr>
<?php foreach ($this->articles as $article):?>
        <tr>
            <td class="realblog_table_line">
                <input type="checkbox" name="realblog_ids[]" value="<?=$this->escape($article->id)?>">
            </td>
            <td class="realblog_table_line">
                <a href="<?=$this->deleteUrl($article)?>">
                    <img src="<?=$this->deleteIcon?>" title="<?=$this->text('tooltip_delete')?>" alt="<?=$this->text('tooltip_delete')?>">
                </a>
            </td>
            <td class="realblog_table_line">
                <a href="<?=$this->modifyUrl($article)?>">
                    <img src="<?=$this->modifyIcon?>" title="<?=$this->text('tooltip_modify')?>" alt="<?=$this->text('tooltip_modify')?>">
                </a>
            </td>
            <td class="realblog_table_line"><?=$this->escape($article->id)?></td>
            <td class="realblog_table_line"><?=$this->formatDate($article)?></td>
            <td class="realblog_table_line"><?=$this->escape($article->status)?></td>
            <td class="realblog_table_line"><?=$this->escape($article->feedable)?></td>
            <td class="realblog_table_line"><?=$this->escape($article->commentable)?></td>
        </tr>
        <tr>
            <td colspan="8" class="realblog_table_title"><?=$this->escape($article->title)?></td>
        </tr>
<?php endforeach?>
    </table>
<?php if ($this->hasBottomPagination):?>
    <?=$this->pagination->render()?>
<?php endif?>
    <input type="hidden" name="realblog_page" value="<?=$this->page?>">
</form>
