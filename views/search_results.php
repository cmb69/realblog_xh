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
    <span><?=$this->text('search_searched_for')?></span>
    <span class="realblog_searchterm"><?=$words?></span>
  </p>
  <p class="realblog_searchresult_body">
    <span><?=$this->text('search_result')?></span>
    <span class="realblog_searchcount"><?=$count?></span>
  </p>
  <p class="realblog_searchresult_foot">
    <a class="realblog_button" href="<?=$url?>"><?=$this->text($key)?></a>
  </p>
</div>
