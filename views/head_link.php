<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $url
 */
?>

<link rel="alternate" type="application/rss+xml" href="<?=$this->esc($url)?>">
