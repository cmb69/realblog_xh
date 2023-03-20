<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $imageFolder
 * @var int $page
 * @var int $prevPage
 * @var int $nextPage
 * @var int $lastPage
 * @var list<array{id:int,date:string,status:int,categories:string,title:string,feedable:bool,commentable:bool,delete_url:string,edit_url:string}> $articles
 * @var string $actionUrl
 * @var list<array{value:int,label:string,checked:string}> $states
 */
?>
<!-- realblog articles form -->
<div class="realblog_articles_form">
  <h1>Realblog – <?=$this->text('story_overview')?></h1>
  <form method="get" action="<?=$actionUrl?>">
    <input type="hidden" name="selected" value="realblog">
    <input type="hidden" name="admin" value="plugin_main">
    <table class="realblog_table">
      <thead>
        <tr>
          <th colspan="3">
            <button name="action" value="plugin_text" title="<?=$this->text('tooltip_refresh')?>">
              <img src="<?=$imageFolder?>refresh.png" alt="<?=$this->text('tooltip_refresh')?>">
            </button>
            <button name="action" value="delete_selected" title="<?=$this->text('tooltip_delete_selected')?>">
              <img src="<?=$imageFolder?>delete-selected.png" alt="<?=$this->text('tooltip_delete_selected')?>">
            </button>
            <button name="action" value="change_status" title="<?=$this->text('tooltip_change_status')?>">
              <img src="<?=$imageFolder?>change-status.png" alt="<?=$this->text('tooltip_change_status')?>">
            </button>
            <button name="action" value="create" title="<?=$this->text('tooltip_create')?>">
              <img src="<?=$imageFolder?>create.png" alt="<?=$this->text('tooltip_create')?>">
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
<?foreach ($articles as $article):?>
        <tr>
          <td>
            <input type="checkbox" name="realblog_ids[]" value="<?=$article['id']?>">
          </td>
          <td>
            <a href="<?=$article['delete_url']?>">
              <img src="<?=$imageFolder?>delete.png" title="<?=$this->text('tooltip_delete')?>" alt="<?=$this->text('tooltip_delete')?>">
            </a>
          </td>
          <td class="realblog_table_line">
            <a href="<?=$article['edit_url']?>">
              <img src="<?=$imageFolder?>edit.png" title="<?=$this->text('tooltip_edit')?>" alt="<?=$this->text('tooltip_edit')?>">
            </a>
          </td>
          <td><?=$article['id']?></td>
          <td><?=$article['date']?></td>
          <td><?=$article['status']?></td>
          <td><?=$article['feedable']?></td>
          <td><?=$article['commentable']?></td>
        </tr>
        <tr>
          <td colspan="5" class="realblog_table_title"><?=$article['title']?></td>
          <td colspan="3" class="realblog_table_categories"><?=$article['categories']?></td>
        </tr>
<?endforeach?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="8">
<?foreach ($states as $status):?>
            <input type="hidden" name="realblog_filter[<?=$status['value']?>]" value="">
            <label>
              <input type="checkbox" name="realblog_filter[<?=$status['value']?>]" <?=$status['checked']?>>
              <?=$this->text($status['label'])?>
            </label>
<?endforeach?>
            <button title="<?=$this->text('tooltip_filter')?>">
              <img src="<?=$imageFolder?>filter.png" alt="<?=$this->text('tooltip_filter')?>">
            </button>
          </td>
        </tr>
        <tr>
          <td colspan="8">
            <input type="text" name="realblog_page" value="<?=$page?>" size="2">
            / <?=$lastPage?>
            <button name="realblog_page" value="1" title="<?=$this->text('tooltip_first')?>">
              <img src="<?=$imageFolder?>first.png" alt="<?=$this->text('tooltip_first')?>">
            </button>
            <button name="realblog_page" value="<?=$prevPage?>" title="<?=$this->text('tooltip_previous')?>">
              <img src="<?=$imageFolder?>prev.png" alt="<?=$this->text('tooltip_previous')?>">
            </button>
            <button name="realblog_page" value="<?=$nextPage?>" title="<?=$this->text('tooltip_next')?>">
              <img src="<?=$imageFolder?>next.png" alt="<?=$this->text('tooltip_next')?>">
            </button>
            <button name="realblog_page" value="<?=$lastPage?>" title="<?=$this->text('tooltip_last')?>">
              <img src="<?=$imageFolder?>last.png" alt="<?=$this->text('tooltip_last')?>">
            </button>
          </td>
        </tr>
      </tfoot>
    </table>
  </form>
</div>
