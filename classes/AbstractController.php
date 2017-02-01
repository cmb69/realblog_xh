<?php

/**
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

abstract class AbstractController
{
    protected $config;

    protected $text;

    public function __construct()
    {
        global $plugin_cf, $plugin_tx;

        $this->config = $plugin_cf['realblog'];
        $this->text = $plugin_tx['realblog'];
    }
}
