<?php

use Plib\View;

/**
 * @var View $this
 * @var string $url
 * @var string $managing_editor
 * @var bool $has_logo
 * @var string $image_url
 * @var list<array{title:string,url:string,teaser:string,date:string}> $articles
 */
?>
<rss version="2.0">
  <channel>
    <title><?=$this->text('rss_title')?></title>
    <description><?=$this->text('rss_description')?></description>
    <link><?=$this->esc($url)?></link>
    <language><?=$this->text('rss_language')?></language>
    <copyright><?=$this->text('rss_copyright')?></copyright>
    <managingEditor><?=$this->esc($managing_editor)?></managingEditor>
<?if ($has_logo):?>
    <image>
      <url><?=$this->esc($image_url)?></url>
      <link><?=$this->esc($url)?></link>
      <title><?=$this->text('rss_title')?></title>
    </image>
<?endif?>
<?foreach ($articles as $article):?>
    <item>
      <title><?=$this->esc($article['title'])?></title>
      <link><?=$this->esc($article['url'])?></link>
      <description><?=$this->esc($article['teaser'])?></description>
      <pubDate><?=$this->esc($article['date'])?></pubDate>
    </item>
<?endforeach?>
  </channel>
</rss>
