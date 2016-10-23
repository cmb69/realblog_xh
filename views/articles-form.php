<!-- realblog articles form -->
<h1>Realblog – <?=$this->text('story_overview')?></h1>
<form method="get" action="<?=$this->actionUrl?>">
    <input type="hidden" name="selected" value="realblog">
    <input type="hidden" name="admin" value="plugin_main">
    <table class="realblog_table">
        <tr>
            <td class="realblog_table_header" colspan="3">
                <button name="action" value="plugin_text" title="<?=$this->text('tooltip_refresh')?>">
                    <img src="<?=$this->imageFolder?>refresh.png" alt="<?=$this->text('tooltip_refresh')?>">
                </button>
                <button name="action" value="batchdelete" title="<?=$this->text('tooltip_deleteall')?>">
                    <img src="<?=$this->imageFolder?>batch-delete.png" alt="<?=$this->text('tooltip_deleteall')?>">
                </button>
                <button name="action" value="change_status" title="<?=$this->text('tooltip_changestatus')?>">
                    <img src="<?=$this->imageFolder?>change-status.png" alt="<?=$this->text('tooltip_changestatus')?>">
                </button>
                <button name="action" value="add_realblog" title="<?=$this->text('tooltip_add')?>">
                    <img src="<?=$this->imageFolder?>add.png" alt="<?$this->text('tooltip_add')?>"
                </button>
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
                    <img src="<?=$this->imageFolder?>delete.png" title="<?=$this->text('tooltip_delete')?>" alt="<?=$this->text('tooltip_delete')?>">
                </a>
            </td>
            <td class="realblog_table_line">
                <a href="<?=$this->modifyUrl($article)?>">
                    <img src="<?=$this->imageFolder?>edit.png" title="<?=$this->text('tooltip_modify')?>" alt="<?=$this->text('tooltip_modify')?>">
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
        <tr>
            <td colspan="5" class="realblog_table_footer">
<?php foreach ($this->states as $i => $status):?>
                <input type="hidden" name="realblog_filter<?=$i?>" value="">
                <label>
                    <input type="checkbox" name="realblog_filter<?=$i?>" <?php if ($this->hasFilter($i)) echo 'checked'?>>
                    <?=$this->text($status)?>
                </label>
<?php endforeach?>
                <button title="<?=$this->text('tooltip_filter')?>">
                    <img src="<?=$this->imageFolder?>filter.png" alt="<?=$this->text('tooltip_filter')?>">
                </button>
            </td>
            <td colspan="3" class="realblog_table_footer">
                <input type="text" name="realblog_page" value="<?=$this->page?>" size="2">
                / <?=$this->lastPage?>
                <button name="realblog_page" value="1" title="<?=$this->text('tooltip_first')?>">
                    <img src="<?=$this->imageFolder?>first.png" alt="<?=$this->text('tooltip_first')?>">
                </button>
                <button name="realblog_page" value="<?=$this->prevPage?>" title="<?=$this->text('tooltip_previous')?>">
                    <img src="<?=$this->imageFolder?>prev.png" alt="<?=$this->text('tooltip_previous')?>">
                </button>
                <button name="realblog_page" value="<?=$this->nextPage?>" title="<?=$this->text('tooltip_next')?>">
                    <img src="<?=$this->imageFolder?>next.png" alt="<?=$this->text('tooltip_next')?>">
                </button>
                <button name="realblog_page" value="<?=$this->lastPage?>" title="<?=$this->text('tooltip_last')?>">
                    <img src="<?=$this->imageFolder?>last.png" alt="<?=$this->text('tooltip_last')?>">
                </button>
            </td>
        </tr>
    </table>
</form>
