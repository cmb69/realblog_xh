<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $url
 * @var string $managingEditor
 * @var bool $hasLogo
 * @var string $imageUrl
 * @var list<array{title:string,url:string,teaser:html,date:string}> $articles
 */
?>
<rss version="2.0">
  <channel>
    <title><?=$this->text('rss_title')?></title>
    <description><?=$this->text('rss_description')?></description>
    <link><?=$this->esc($url)?></link>
    <language><?=$this->text('rss_language')?></language>
    <copyright><?=$this->text('rss_copyright')?></copyright>
    <managingEditor><?=$this->esc($managingEditor)?></managingEditor>
<?if ($hasLogo):?>
    <image>
      <url><?=$this->esc($imageUrl)?></url>
      <link><?=$this->esc($url)?></link>
      <title><?=$this->text('rss_title')?></title>
    </image>
<?endif?>
<?foreach ($articles as $article):?>
    <item>
      <title><?=$this->esc($article['title'])?></title>
      <link><?=$this->esc($article['url'])?></link>
      <description><?=$this->raw($article['teaser'])?></description>
      <pubDate><?=$this->esc($article['date'])?></pubDate>
    </item>
<?endforeach?>
  </channel>
</rss>
