<!-- realblog info -->
<div class="realblog_info">
    <h1>Realblog</h1>
    <img src="<?=$this->logoPath?>" class="realblog_logo" alt="<?=$this->text('alt_logo')?>">
    <p>Version: <?=$this->version?></p>
    <p>
        Copyright © 2006-2010 Jan Kanters<br>
        Copyright © 2010-2014 <a href="http://www.ge-webdesign.de/" target="_blank">Gert Ebersbach</a><br>
        Copyright © 2014-2017 <a href="http://3-magi.net/" target="_blank">Christoph M. Becker</a>
    </p>
    <p class="realblog_license">
        This program is free software: you can redistribute it and/or modify it
        under the terms of the GNU General Public License as published by the Free
        Software Foundation, either version 3 of the License, or (at your option)
        any later version.
    </p>
    <p class="realblog_license">
        This program is distributed in the hope that it will be useful, but
        <em>without any warranty</em>; without even the implied warranty of
        <em>merchantability</em> or <em>fitness for a particular purpose</em>. See
        the GNU General Public License for more details.
    </p>
    <p class="realblog_license">
        You should have received a copy of the GNU General Public License along with
        this program. If not, see <a href="http://www.gnu.org/licenses/"
        target="_blank">http://www.gnu.org/licenses/</a>.
    </p>
</div>
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
