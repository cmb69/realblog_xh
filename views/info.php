<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $version
 * @var string $heading
 * @var list<array{key:string,arg:string,class:string,state:string}> $checks
 */
?>
<!-- realblog info -->
<div class="realblog_info">
  <h1>Realblog <?=$this->esc($version)?></h1>
  <<?=$this->esc($heading)?>><?=$this->text('syscheck_title')?></<?=$this->esc($heading)?>>
  <div class="realblog_systemcheck">
<?foreach ($checks as $check):?>
    <p class="<?=$this->esc($check['class'])?>"><?=$this->text($check['key'], $check['arg'])?>: <?=$this->text($check['state'])?></p>
<?endforeach?>
  </div>
</div>
