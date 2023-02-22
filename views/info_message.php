<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $title
 * @var html $message
 * @var string $url
 */
?>
<!-- realblog info message -->
<h1>Realblog – <?=$this->text($title)?></h1>
<?=$this->raw($message)?>
<p><a href="<?=$this->esc($url)?>"><?=$this->text('blog_back')?></a></p>
