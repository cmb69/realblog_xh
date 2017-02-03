<!-- realblog search results -->
<div class="realblog_searchresult">
    <p class="realblog_searchresult_head">
        <?=$this->text('search_result_head')?>
    </p>
    <p class="realblog_searchresult_body">
        <?=$this->plural('search_result', $this->count, $this->words)?>
    </p>
    <p class="realblog_searchresult_foot">
        <span class="realblog_button"><a href="<?=$this->url?>"><?=$this->text($this->key)?></a></span>
    </p>
</div>
