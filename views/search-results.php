<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var int $count
 * @var string $words
 * @var string $url
 * @var string $key
 */
?>
<!-- realblog search results -->
<div class="realblog_searchresult">
  <p class="realblog_searchresult_head">
    <?=$this->text('search_result_head')?>
  </p>
  <p class="realblog_searchresult_body">
    <?=$this->plural('search_result', $count, $words)?>
  </p>
  <p class="realblog_searchresult_foot">
    <a class="realblog_button" href="<?=$this->esc($url)?>"><?=$this->text($key)?></a>
  </p>
</div>
