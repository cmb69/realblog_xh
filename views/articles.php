<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var list<array{title:string,url:string,categories:string,link_header:bool,date:string,teaser:string,read_more:bool,commentable:bool,comment_count:int}> $articles
 * @var string $pagination
 * @var bool $top_pagination
 * @var bool $bottom_pagination
 * @var string $heading
 * @var bool $heading_above_meta
 */
?>
<!-- realblog articles -->
<div class="realblog_show_box">
<?if ($top_pagination):?>
  <?=$pagination?>
<?endif?>
  <div id="realblog_entries_preview" class="realblog_entries_preview">
<?foreach ($articles as $article):?>
    <div class="realblog_entry_preview">
<?if ($heading_above_meta):?>
      <div class="realblog_article_meta">
        <span class="realblog_meta_date"><?=$this->text('message_published_on', $article['date'])?></span>
<?  if ($article['categories']):?>
        <span class="realblog_meta_categories"><?=$this->text('message_filed_under', $article['categories'])?></span>
<?  endif?>
<?  if ($article['commentable']):?>
        <span class="realblog_meta_comments"><?=$this->plural('message_comments', $article['comment_count'])?></span>
<?  endif?>
      </div>
<?endif?>
      <<?=$heading?>>
<?  if ($article['link_header']):?>
        <a href="<?=$article['url']?>" title="<?=$this->text('tooltip_view')?>">
<?  endif?>
        <?=$article['title']?>
<?  if ($article['link_header']):?>
        </a>
<?  endif?>
      </<?=$heading?>>
<?if (!$heading_above_meta):?>
      <div class="realblog_article_meta">
        <span class="realblog_meta_date"><?=$this->text('message_published_on', $article['date'])?></span>
<?  if ($article['categories']):?>
        <span class="realblog_meta_categories"><?=$this->text('message_filed_under', $article['categories'])?></span>
<?  endif?>
<?  if ($article['commentable']):?>
        <span class="realblog_meta_comments"><?=$this->plural('message_comments', $article['comment_count'])?></span>
<?  endif?>
      </div>
<?endif?>
      <div class="realblog_show_story"><?=$article['teaser']?></div>
<?  if ($article['link_header']):?>
      <div class="realblog_entry_footer">
        <p class="realblog_read_more">
          <a class="realblog_button" href="<?=$article['url']?>" title="<?=$this->text('tooltip_view')?>"><?=$this->text('read_more')?></a>
        </p>
      </div>
<?  endif?>
    </div>
<?endforeach?>
  </div>
<?if ($bottom_pagination):?>
  <?=$pagination?>
<?endif?>
</div>
