<!-- realblog search form -->
<form class="realblog_search_form" method="get" action="<?=$this->actionUrl?>">
    <input type="hidden" name="selected" value="<?=$this->pageUrl?>">
    <input type="text" name="realblog_search" class="realblog_search_input"
           title="<?=$this->text('search_hint')?>" placeholder="<?=$this->text('search_placeholder')?>">
    <button><?=$this->text('search_button')?></button>
</form>
