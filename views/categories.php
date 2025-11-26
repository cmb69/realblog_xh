<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var array<string,string> $categories
 */
?>

<nav>
  <ul>
<?foreach ($categories as $category => $url):?>
    <li><a href="<?=$this->esc($url)?>"><?=$this->esc($category)?></a></li>
<?endforeach?>
  </ul>
</nav>
