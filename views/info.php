<!-- realblog info -->
<div class="realblog_info">
    <h1>Realblog <?=$this->version?></h1>
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
