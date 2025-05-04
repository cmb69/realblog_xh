<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var int $id
 * @var int $version
 * @var int $status
 * @var string $title
 * @var string $teaser
 * @var string $body
 * @var bool $feedable
 * @var bool $commentable
 * @var string $date
 * @var string $publishing_date
 * @var string $archiving_date
 * @var string $page_title
 * @var string $csrfToken
 * @var bool $isAutoPublish
 * @var bool $isAutoArchive
 * @var list<array{int,string,string}> $states
 * @var string $categories
 * @var string $button
 * @var list<array{string}> $errors
 * @var string $script
 */
?>
<!-- realblog article form -->
<script type="module" src="<?=$this->esc($script)?>"></script>
<div class="realblog_fields_block">
  <h1>Realblog – <?=$page_title?></h1>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <form name="realblog" method="post">
    <input type="hidden" name="realblog_id" value="<?=$id?>">
    <input type="hidden" name="realblog_version" value="<?=$version?>">
    <input type="hidden" name="xh_csrf_token" value="<?=$csrfToken?>">
    <table>
      <tr>
        <td><label for="realblog_date1" class="realblog_label"><?=$this->text('date_label')?></label></td>
        <td><label for="realblog_date2" class="realblog_label"><?=$this->text('startdate_label')?></label></td>
        <td><label for="realblog_date3" class="realblog_label"><?=$this->text('enddate_label')?></span></label>
      </tr>
      <tr>
        <td>
          <input type="date" name="realblog_date" id="realblog_date1" required="required" value="<?=$date?>">
        </td>
        <td>
<?if ($isAutoPublish):?>
          <input type="date" name="realblog_startdate" id="realblog_date2" required="required" value="<?=$publishing_date?>">
<?else:?>
          <span><?=$this->text('startdate_hint')?></span>
          <input type="hidden" name="realblog_startdate" value="<?=$publishing_date?>">
<?endif?>
        </td>
        <td>
<?if ($isAutoArchive):?>
          <input type="date" name="realblog_enddate" id="realblog_date3" required="required" value="<?=$archiving_date?>">
<?else:?>
          <span><?=$this->text('enddate_hint')?></span>
          <input type="hidden" name="realblog_enddate" value="<?=$archiving_date?>">
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
<?foreach ($states as [$value, $label, $selected]):?>
            <option value="<?=$value?>" <?=$selected?>><?=$this->text($label)?></option>
<?endforeach?>
          </select>
        </td>
        <td>
          <label>
            <input type="hidden" name="realblog_comments" value="">
            <input type="checkbox" name="realblog_comments" value="1" <?=$commentable?>>
            <span><?=$this->text('comment_label')?></span>
          </label>
        </td>
        <td>
          <label>
            <input type="hidden" name="realblog_rssfeed" value="">
            <input type="checkbox" name="realblog_rssfeed" value="1" <?=$feedable?>>
            <span><?=$this->text('label_rss')?></span>
          </label>
        </td>
      </tr>
    </table>
    <p>
      <label for="realblog_categories" class="realblog_label"><?=$this->text('label_categories')?></label>
      <input type="text" id="realblog_categories" name="realblog_categories" value="<?=$categories?>" size="50">
      <select id="realblog_category_select">
        <option><?=$this->text('label_category_add')?></option>
      </select>
    </p>
    <p>
      <label for="realblog_title" class="realblog_label"><?=$this->text('title_label')?></label>
      <input type="text" id="realblog_title" name="realblog_title" value="<?=$title?>" size="50">
    </p>
    <p>
      <label for="realblog_headline" class="realblog_label"><?=$this->text('headline_label')?></label>
      <textarea class="realblog_headline_field" id="realblog_headline" name="realblog_headline" rows="6" cols="60"><?=$teaser?></textarea>
    </p>
    <p>
      <label for="realblog_story" class="realblog_label"><?=$this->text('story_label')?></label>
      <textarea class="realblog_story_field" id="realblog_story" name="realblog_story" rows="30" cols="80"><?=$body?></textarea>
    </p>
    <p style="text-align: center"><button name="realblog_do"><?=$this->text($button)?></button></p>
  </form>
</div>
