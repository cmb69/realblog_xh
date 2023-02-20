<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2023 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Realblog;

use Realblog\Infra\SystemCheck;
use Realblog\Infra\View;

class InfoController
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $conf;

    /** @var View */
    private $view;

    /**
     * @param string $pluginFolder
     * @param array<string,string> $conf
     */
    public function __construct($pluginFolder, array $conf, View $view)
    {
        $this->pluginFolder = $pluginFolder;
        $this->conf = $conf;
        $this->view = $view;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        $data = [
            'version' => Plugin::VERSION,
            'heading' => $this->conf['heading_level'],
            'checks' => (new SystemCheck)->getChecks(),
            'imageURL' =>
                /**
                 * @param string $state
                 * @return string
                 */
                function ($state) {
                    return "{$this->pluginFolder}images/$state.png";
                },
        ];
        return $this->view->render('info', $data);
    }
}
