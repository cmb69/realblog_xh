<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var string $heading
 * @var list<array{label:string,state:string,image:string}> $checks
 */
?>
<!-- realblog info -->
<div class="realblog_info">
  <h1>Realblog <?=$this->esc($version)?></h1>
  <<?=$this->esc($heading)?>><?=$this->text('syscheck_title')?></<?=$this->esc($heading)?>>
  <ul class="realblog_systemcheck">
<?foreach ($checks as $check):?>
    <li>
      <img src="<?=$this->esc($check['image'])?>" alt="<?=$this->text("syscheck_$check[state]")?>">
      <?=$this->esc($check['label'])?>
    </li>
<?endforeach?>
  </ul>
</div>
