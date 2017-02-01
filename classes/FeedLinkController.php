<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class FeedLinkController extends AbstractController
{
    public function defaultAction()
    {
        global $sn, $pth;

        return <<<HTML
<!-- realblog feed link -->
<a href="$sn?realblog_feed=rss">
    <img src="{$pth['folder']['plugins']}realblog/images/rss.png"
         alt="{$this->text['rss_tooltip']}" title="{$this->text['rss_tooltip']}"
         style="border: 0">
</a>
HTML;
    }
}
