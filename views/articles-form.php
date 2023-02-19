<!-- realblog articles form -->
<div class="realblog_articles_form">
    <h1>Realblog – <?=$this->text('story_overview')?></h1>
    <form method="get" action="<?=$this->actionUrl?>">
        <input type="hidden" name="selected" value="realblog">
        <input type="hidden" name="admin" value="plugin_main">
        <table class="realblog_table">
            <thead>
                <tr>
                    <th colspan="3">
                        <button name="action" value="plugin_text" title="<?=$this->text('tooltip_refresh')?>">
                            <img src="<?=$this->imageFolder?>refresh.png" alt="<?=$this->text('tooltip_refresh')?>">
                        </button>
                        <button name="action" value="delete_selected" title="<?=$this->text('tooltip_delete_selected')?>">
                            <img src="<?=$this->imageFolder?>delete-selected.png" alt="<?=$this->text('tooltip_delete_selected')?>">
                        </button>
                        <button name="action" value="change_status" title="<?=$this->text('tooltip_change_status')?>">
                            <img src="<?=$this->imageFolder?>change-status.png" alt="<?=$this->text('tooltip_change_status')?>">
                        </button>
                        <button name="action" value="create" title="<?=$this->text('tooltip_create')?>">
                            <img src="<?=$this->imageFolder?>create.png" alt="<?=$this->text('tooltip_create')?>">
                        </button>
                    </th>
                    <th><?=$this->text('id_label')?></th>
                    <th><?=$this->text('date_label')?></th>
                    <th><?=$this->text('label_status')?></th>
                    <th><?=$this->text('label_rss')?></th>
                    <th><?=$this->text('comments_onoff')?></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($this->articles as $article):?>
                <tr>
                    <td>
                        <input type="checkbox" name="realblog_ids[]" value="<?=$this->escape($article->id)?>">
                    </td>
                    <td>
                        <a href="<?=$this->deleteUrl($article)?>">
                            <img src="<?=$this->imageFolder?>delete.png" title="<?=$this->text('tooltip_delete')?>" alt="<?=$this->text('tooltip_delete')?>">
                        </a>
                    </td>
                    <td class="realblog_table_line">
                        <a href="<?=$this->editUrl($article)?>">
                            <img src="<?=$this->imageFolder?>edit.png" title="<?=$this->text('tooltip_edit')?>" alt="<?=$this->text('tooltip_edit')?>">
                        </a>
                    </td>
                    <td><?=$this->escape($article->id)?></td>
                    <td><?=$this->formatDate($article)?></td>
                    <td><?=$this->escape($article->status)?></td>
                    <td><?=$this->escape($article->feedable)?></td>
                    <td><?=$this->escape($article->commentable)?></td>
                </tr>
                <tr>
                    <td colspan="5" class="realblog_table_title"><?=$this->escape($article->title)?></td>
                    <td colspan="3" class="realblog_table_categories"><?=$this->escape($article->categories)?></td>
                </tr>
<?php endforeach?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8">
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
                </tr>
                <tr>
                    <td colspan="8">
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
            </tfoot>
        </table>
    </form>
</div>
