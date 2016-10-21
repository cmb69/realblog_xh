<!-- realblog search form -->
<form class="realblog_search_form" method="get" action="<?=$this->actionUrl?>">
    <input type="hidden" name="selected" value="<?=$this->pageUrl?>">
    <p>
        <input type="text" name="realblog_search" size="15" class="realblog_search_input"
               maxlength="64" title="<?=$this->text('search_hint')?>" placeholder="<?=$this->text('search_placeholder')?>">
        <input type="submit" value="<?=$this->text('search_button')?>">
    </p>
</form>
