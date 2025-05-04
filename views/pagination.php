<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var int $itemCount
 * @var list<array{num:int,url:?string}|null> $pages
 */
?>
<!-- realblog pagination -->
<div class="realblog_pagination">
  <span class="realblog_pag_count"><?=$this->plural('article_count', $itemCount)?></span>
<?foreach ($pages as $page):?>
<?  if (!isset($page)):?>
  <span class="realblog_pag_ellipsis">…</span>
<?  elseif (!isset($page['url'])):?>
  <span class="realblog_pag_current"><?=$this->esc($page['num'])?></span>
<?  else:?>
  <a class="realblog_button" href="<?=$this->esc($page['url'])?>"><?=$this->esc($page['num'])?></a>
<?  endif?>
<?endforeach?>
</div>
