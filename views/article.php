<?php

use Plib\View;

/**
 * @var View $this
 * @var string $title
 * @var string $heading
 * @var bool $heading_above_meta
 * @var bool $is_admin
 * @var bool $wants_comments
 * @var string $back_text
 * @var string $back_url
 * @var string|null $back_to_search_url
 * @var string $edit_url
 * @var string|null $edit_comments_url
 * @var int|null $comment_count
 * @var string $date
 * @var string $categories
 * @var string $story
 * @var string $comments
 */
?>
<!-- realblog article -->
<div class="realblog_article">

  <div class="realblog_show_box">

    <div class="realblog_buttons">
<?if (isset($back_to_search_url)):?>
      <a class="realblog_button" href="<?=$this->esc($back_to_search_url)?>"><?=$this->text('search_back')?></a>
<?endif?>
      <a class="realblog_button" href="<?=$this->esc($back_url)?>"><?=$this->text($back_text)?></a>
<?if ($is_admin):?>
<?  if ($wants_comments && isset($edit_comments_url)):?>
      <a class="realblog_button" href="<?=$this->esc($edit_comments_url)?>"><?=$this->text('comment_edit')?></a>
<?  endif?>
      <a class="realblog_button" href="<?=$this->esc($edit_url)?>"><?=$this->text('entry_edit')?></a>
<?endif?>
    </div>

<?if (!$heading_above_meta):?>
    <<?=$this->esc($heading)?>><?=$this->esc($title)?></<?=$this->esc($heading)?>>
<?endif?>
    <div class="realblog_article_meta">
      <span class="realblog_meta_date"><?=$this->text('message_published_on', $date)?></span>
<?if ($categories):?>
      <span class="realblog_meta_categories"><?=$this->text('message_filed_under', $categories)?></span>
<?endif?>
<?if (isset($comment_count)):?>
      <span class="realblog_meta_comments"><?=$this->plural('message_comments', $comment_count)?></span>
<?endif?>
    </div>
<?if ($heading_above_meta):?>
    <<?=$this->esc($heading)?>><?=$this->esc($title)?></<?=$this->esc($heading)?>>
<?endif?>
    <div class="realblog_show_story_entry"><?=$this->raw($story)?></div>

    <div class="realblog_buttons">
<?if (isset($back_to_search_url)):?>
      <a class="realblog_button" href="<?=$this->esc($back_to_search_url)?>"><?=$this->text('search_back')?></a>
<?endif?>
      <a class="realblog_button" href="<?=$this->esc($back_url)?>"><?=$this->text($back_text)?></a>
<?if ($is_admin):?>
<?  if ($wants_comments && isset($edit_comments_url)):?>
      <a class="realblog_button" href="<?=$this->esc($edit_comments_url)?>"><?=$this->text('comment_edit')?></a>
<?  endif?>
      <a class="realblog_button" href="<?=$this->esc($edit_url)?>"><?=$this->text('entry_edit')?></a>
<?endif?>
    </div>

  </div>

<?if ($wants_comments):?>
  <?=$comments?>
<?endif?>

</div>
