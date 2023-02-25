<?php

use Realblog\Infra\View;
use Realblog\Value\FullArticle;

/**
 * @var View $this
 * @var FullArticle $article
 * @var string $date
 * @var string $publishing_date
 * @var string $archiving_date
 * @var string $title
 * @var string $actionUrl
 * @var string $action
 * @var string $csrfToken
 * @var bool $isAutoPublish
 * @var bool $isAutoArchive
 * @var list<string> $states
 * @var string $categories
 * @var string $button
 */
?>
<!-- realblog article form -->
<div class="realblog_fields_block">
  <h1>Realblog – <?=$this->esc($title)?></h1>
  <form name="realblog" method="post" action="<?=$this->esc($actionUrl)?>">
    <input type="hidden" name="action" value="<?=$this->esc($action)?>">
    <input type="hidden" name="realblog_id" value="<?=$this->esc($article->id)?>">
    <input type="hidden" name="realblog_version" value="<?=$this->esc($article->version)?>">
    <input type="hidden" name="xh_csrf_token" value="<?=$this->esc($csrfToken)?>">
    <table>
      <tr>
        <td><label for="date1" class="realblog_label"><?=$this->text('date_label')?></label></td>
        <td><label for="date2" class="realblog_label"><?=$this->text('startdate_label')?></label></td>
        <td><label for="date3" class="realblog_label"><?=$this->text('enddate_label')?></span></label>
      </tr>
      <tr>
        <td>
<?if ($article->id === 0):?>
          <input type="hidden" name="realblog_date_exact" value="<?=$this->esc($article->date)?>">
          <input type="hidden" name="realblog_date_old" value="<?=$this->esc($date)?>">
<?endif?>
          <input type="date" name="realblog_date" id="realblog_date1" required="required" value="<?=$this->esc($date)?>">
        </td>
        <td>
<?if ($isAutoPublish):?>
          <input type="date" name="realblog_startdate" id="realblog_date2" required="required" value="<?=$this->esc($publishing_date)?>">
<?else:?>
          <span><?=$this->text('startdate_hint')?></span>
          <input type="hidden" name="realblog_startdate" value="<?=$this->esc($publishing_date)?>">
<?endif?>
        </td>
        <td>
<?if ($isAutoArchive):?>
          <input type="date" name="realblog_enddate" id="realblog_date3" required="required" value="<?=$this->esc($archiving_date)?>">
<?else:?>
          <span><?=$this->text('enddate_hint')?></span>
          <input type="hidden" name="realblog_enddate" value="<?=$this->esc($archiving_date)?>">
<?endif?>
        </td>
      </tr>
      <tr>
        <td><label for="realblog_status" class="realblog_label"><?=$this->text('label_status')?></label></td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td>
          <select id="realblog_status" name="realblog_status">
<?foreach ($states as $i => $state):?>
            <option value="<?=$this->esc($i)?>" <?php if ($article->status === $i) echo 'selected'?>><?=$this->text($state)?></option>
<?endforeach?>
          </select>
        </td>
        <td>
          <label>
            <input type="checkbox" name="realblog_comments" <?php if ($article->commentable) echo 'checked'?>>
            <span><?=$this->text('comment_label')?></span>
          </label>
        </td>
        <td>
          <label>
            <input type="checkbox" name="realblog_rssfeed" <?php if ($article->feedable) echo 'checked'?>>
            <span><?=$this->text('label_rss')?></span>
          </label>
        </td>
      </tr>
    </table>
    <p>
      <label for="realblog_categories" class="realblog_label"><?=$this->text('label_categories')?></label>
      <input type="text" id="realblog_categories" name="realblog_categories" value="<?=$this->esc($categories)?>" size="50">
      <select id="realblog_category_select">
        <option><?=$this->text('label_category_add')?></option>
      </select>
    </p>
    <p>
      <label for="realblog_title" class="realblog_label"><?=$this->text('title_label')?></label>
      <input type="text" id="realblog_title" name="realblog_title" value="<?=$this->esc($article->title)?>" size="50">
    </p>
    <p>
      <label for="realblog_headline" class="realblog_label"><?=$this->text('headline_label')?></label>
      <textarea class="realblog_headline_field" id="realblog_headline" name="realblog_headline" rows="6" cols="60"><?=$this->esc($article->teaser)?></textarea>
    </p>
    <p>
      <label for="realblog_story" class="realblog_label"><?=$this->text('story_label')?></label>
      <textarea class="realblog_story_field" id="realblog_story" name="realblog_story" rows="30" cols="80"><?=$this->esc($article->body)?></textarea>
    </p>
    <p style="text-align: center"><input type="submit" name="save" value="<?=$this->text($button)?>"></p>
  </form>
</div>
