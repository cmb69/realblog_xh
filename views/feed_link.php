<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $url
 * @var string $target
 * @var string $image
 */
?>
<!-- realblog feed link -->
<a href="<?=$url?>" target="<?=$target?>">
  <img src="<?=$image?>" alt="<?=$this->text('rss_tooltip')?>" title="<?=$this->text('rss_tooltip')?>" style="border: 0">
</a>
