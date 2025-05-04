<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $url
 * @var string $target
 * @var string $image
 */
?>
<!-- realblog feed link -->
<a href="<?=$this->esc($url)?>" target="<?=$this->esc($target)?>">
  <img src="<?=$this->esc($image)?>" alt="<?=$this->text('rss_tooltip')?>" title="<?=$this->text('rss_tooltip')?>" style="border: 0">
</a>
