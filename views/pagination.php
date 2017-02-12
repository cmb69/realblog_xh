<!-- realblog pagination -->
<div class="realblog_pagination">
    <span class="realblog_pag_count"><?=$this->plural('article_count', $this->itemCount)?></span>
<?php foreach ($this->pages as $page):?>
<?php   if (!isset($page)):?>
    <span class="realblog_pag_ellipsis">…</span>
<?php   elseif ($page == $this->currentPage):?>
    <span class="realblog_pag_current"><?=$this->escape($page)?></span>
<?php   else:?>
    <a class="realblog_button" href="<?=$this->url($page)?>"><?=$this->escape($page)?></a>
<?php   endif?>
<?php endforeach?>
</div>
