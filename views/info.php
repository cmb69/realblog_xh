<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var string $heading
 * @var list<array{label:string,state:string,state_label:string}> $checks
 */
?>
<!-- realblog info -->
<div class="realblog_info">
  <h1>Realblog <?=$this->esc($version)?></h1>
  <<?=$this->esc($heading)?>><?=$this->text('syscheck_title')?></<?=$this->esc($heading)?>>
  <div class="realblog_systemcheck">
<?foreach ($checks as $check):?>
    <p class="xh_<?=$this->esc($check['state'])?>"><?=$this->text('syscheck_message', $check['label'], $check['state_label'])?></p>
<?endforeach?>
  </div>
</div>
