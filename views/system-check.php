<!-- realblog system check -->
<h4><?=$this->text('syscheck_title')?></h4>
<ul class="realblog_systemcheck">
<?php foreach ($this->checks as $label => $state):?>
    <li>
        <img src="<?=$this->imageURL($state)?>" alt="<?=$this->text("syscheck_$state")?>">
        <?=$label?>
    </li>
<?php endforeach?>
</ul>
