<?php

use Plib\View;

/**
 * @var View $this
 * @var string $imageFolder
 * @var int $page
 * @var int $prevPage
 * @var int $nextPage
 * @var int $lastPage
 * @var list<array{id:int,date:string,status:int,categories:string,title:string,feedable:bool,commentable:bool,delete_url:string,edit_url:string}> $articles
 * @var list<array{int,string,string}> $states
 */
?>
<!-- realblog articles form -->
<div class="realblog_articles_form">
  <h1>Realblog – <?=$this->text('story_overview')?></h1>
  <form method="get">
    <input type="hidden" name="selected" value="realblog">
    <input type="hidden" name="admin" value="plugin_main">
    <table class="realblog_table">
      <thead>
        <tr>
          <th colspan="3">
            <button name="action" value="plugin_text" title="<?=$this->text('tooltip_refresh')?>">
              <img src="<?=$this->esc($imageFolder)?>refresh.png" alt="<?=$this->text('tooltip_refresh')?>">
            </button>
            <button name="action" value="delete_selected" title="<?=$this->text('tooltip_delete_selected')?>">
              <img src="<?=$this->esc($imageFolder)?>delete-selected.png" alt="<?=$this->text('tooltip_delete_selected')?>">
            </button>
            <button name="action" value="change_status" title="<?=$this->text('tooltip_change_status')?>">
              <img src="<?=$this->esc($imageFolder)?>change-status.png" alt="<?=$this->text('tooltip_change_status')?>">
            </button>
            <button name="action" value="create" title="<?=$this->text('tooltip_create')?>">
              <img src="<?=$this->esc($imageFolder)?>create.png" alt="<?=$this->text('tooltip_create')?>">
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
            <a href="<?=$this->esc($article['delete_url'])?>">
              <img src="<?=$this->esc($imageFolder)?>delete.png" title="<?=$this->text('tooltip_delete')?>" alt="<?=$this->text('tooltip_delete')?>">
            </a>
          </td>
          <td class="realblog_table_line">
            <a href="<?=$this->esc($article['edit_url'])?>">
              <img src="<?=$this->esc($imageFolder)?>edit.png" title="<?=$this->text('tooltip_edit')?>" alt="<?=$this->text('tooltip_edit')?>">
            </a>
          </td>
          <td><?=$article['id']?></td>
          <td><?=$this->esc($article['date'])?></td>
          <td><?=$article['status']?></td>
          <td><?=$article['feedable']?></td>
          <td><?=$article['commentable']?></td>
        </tr>
        <tr>
          <td colspan="5" class="realblog_table_title"><?=$this->esc($article['title'])?></td>
          <td colspan="3" class="realblog_table_categories"><?=$this->esc($article['categories'])?></td>
        </tr>
<?endforeach?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="8">
<?foreach ($states as [$value,$label,$checked]):?>
            <label>
              <input type="checkbox" name="realblog_filter[]" value="<?=$value?>" <?=$this->esc($checked)?>>
              <?=$this->text($label)?>
            </label>
<?endforeach?>
            <button title="<?=$this->text('tooltip_filter')?>">
              <img src="<?=$this->esc($imageFolder)?>filter.png" alt="<?=$this->text('tooltip_filter')?>">
            </button>
          </td>
        </tr>
        <tr>
          <td colspan="8">
            <input type="text" name="realblog_page" value="<?=$page?>" size="2">
            / <?=$lastPage?>
            <button name="realblog_page" value="1" title="<?=$this->text('tooltip_first')?>">
              <img src="<?=$this->esc($imageFolder)?>first.png" alt="<?=$this->text('tooltip_first')?>">
            </button>
            <button name="realblog_page" value="<?=$prevPage?>" title="<?=$this->text('tooltip_previous')?>">
              <img src="<?=$this->esc($imageFolder)?>prev.png" alt="<?=$this->text('tooltip_previous')?>">
            </button>
            <button name="realblog_page" value="<?=$nextPage?>" title="<?=$this->text('tooltip_next')?>">
              <img src="<?=$this->esc($imageFolder)?>next.png" alt="<?=$this->text('tooltip_next')?>">
            </button>
            <button name="realblog_page" value="<?=$lastPage?>" title="<?=$this->text('tooltip_last')?>">
              <img src="<?=$this->esc($imageFolder)?>last.png" alt="<?=$this->text('tooltip_last')?>">
            </button>
          </td>
        </tr>
      </tfoot>
    </table>
  </form>
</div>
