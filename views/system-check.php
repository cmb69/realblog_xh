<!-- realblog system check -->
<div class="realblog_systemcheck_container">
    <<?=$this->heading?>><?=$this->text('syscheck_title')?></<?=$this->heading?>>
    <ul class="realblog_systemcheck">
<?php foreach ($this->checks as $label => $state):?>
        <li>
            <img src="<?=$this->imageURL($state)?>" alt="<?=$this->text("syscheck_$state")?>">
            <?=$label?>
        </li>
<?php endforeach?>
    </ul>
</div>
