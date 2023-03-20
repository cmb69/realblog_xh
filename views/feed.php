<?php

use Realblog\Infra\View;

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
    <link><?=$url?></link>
    <language><?=$this->text('rss_language')?></language>
    <copyright><?=$this->text('rss_copyright')?></copyright>
    <managingEditor><?=$managing_editor?></managingEditor>
<?if ($has_logo):?>
    <image>
      <url><?=$image_url?></url>
      <link><?=$url?></link>
      <title><?=$this->text('rss_title')?></title>
    </image>
<?endif?>
<?foreach ($articles as $article):?>
    <item>
      <title><?=$article['title']?></title>
      <link><?=$article['url']?></link>
      <description><?=$article['teaser']?></description>
      <pubDate><?=$article['date']?></pubDate>
    </item>
<?endforeach?>
  </channel>
</rss>
