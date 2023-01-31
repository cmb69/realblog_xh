<rss version="2.0">
    <channel>
        <title><?=$this->text('rss_title')?></title>
        <description><?=$this->text('rss_description')?></description>
        <link><?=$this->url?></link>
        <language><?=$this->text('rss_language')?></language>
        <copyright><?=$this->text('rss_copyright')?></copyright>
        <managingEditor><?=$this->managingEditor?></managingEditor>
<?php if ($this->hasLogo):?>
        <image>
            <url><?=$this->imageUrl?></url>
            <link><?=$this->url?></link>
            <title><?=$this->text('rss_title')?></title>
        </image>
<?php endif?>
<?php foreach ($this->articles as $article):?>
        <item>
            <title><?=$this->escape($article->title)?></title>
            <link><?=$this->articleUrl($article)?></link>
            <description><?=$this->evaluatedTeaser($article)?></description>
            <pubDate><?=$this->rssDate($article)?></pubDate>
        </item>
<?php endforeach?>
    </channel>
</rss>
