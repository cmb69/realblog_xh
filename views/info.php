<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var string $heading
 * @var list<array{key:string,arg:string,class:string,state:string}> $checks
 */
?>
<!-- realblog info -->
<div class="realblog_info">
  <h1>Realblog <?=$version?></h1>
  <<?=$heading?>><?=$this->text('syscheck_title')?></<?=$heading?>>
  <div class="realblog_systemcheck">
<?foreach ($checks as $check):?>
    <p class="<?=$check['class']?>"><?=$this->text($check['key'], $check['arg'])?>: <?=$this->text($check['state'])?></p>
<?endforeach?>
  </div>
</div>
