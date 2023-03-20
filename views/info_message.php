<?php

use Realblog\Infra\View;

/**
 * @var View $this
 * @var string $title
 * @var string $message
 * @var string $url
 */
?>
<!-- realblog info message -->
<h1>Realblog – <?=$this->text($title)?></h1>
<?=$message?>
<p><a href="<?=$url?>"><?=$this->text('blog_back')?></a></p>
