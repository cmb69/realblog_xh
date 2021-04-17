<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
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

class InfoController
{
    /**
     * @return string
     */
    public function defaultAction()
    {
        global $pth, $plugin_cf;

        $view = new View('info');
        $view->logoPath = "{$pth['folder']['plugins']}realblog/realblog.png";
        $view->version = Realblog::VERSION;
        $view->heading = $plugin_cf['realblog']['heading_level'];
        $view->checks = (new SystemCheck)->getChecks();
        $view->imageURL =
            /**
             * @param string $state
             * @return string
             */
            function ($state) use ($pth) {
                return "{$pth['folder']['plugins']}realblog/images/$state.png";
            };
        return $view->render();
    }
}
