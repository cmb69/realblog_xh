<?php

/**
 * Copyright 2017 Christoph M. Becker
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

class SystemCheck
{
    /**
     * @return string
     */
    public function render()
    {
        global $plugin_cf;

        $view = new View('system-check');
        $view->heading = $plugin_cf['realblog']['heading_level'];
        $view->checks = $this->getChecks();
        $view->imageURL = function ($state) {
            global $pth;

            return "{$pth['folder']['plugins']}realblog/images/$state.png";
        };
        return $view->render();
    }

    /**
     * @return array
     */
    private function getChecks()
    {
        global $pth, $plugin_tx;

        $ptx = $plugin_tx['realblog'];
        $checks = array();
        $phpVersion = '5.4.0';
        $checks[sprintf($ptx['syscheck_phpversion'], $phpVersion)] = $this->checkPHPVersion($phpVersion);
        foreach (array('filter', 'sqlite3') as $extension) {
            $checks[sprintf($ptx['syscheck_extension'], $extension)] = $this->checkExtension($extension);
        }
        $xhVersion = '1.7.0';
        $checks[sprintf($ptx['syscheck_xhversion'], $xhVersion)] = $this->checkXHVersion($xhVersion);
        $folders = array(
            "{$pth['folder']['plugins']}realblog/config",
            "{$pth['folder']['plugins']}realblog/css",
            "{$pth['folder']['plugins']}realblog/languages",
        );
        foreach ($folders as $folder) {
            $checks[sprintf($ptx['syscheck_writable'], $folder)] = $this->checkWritability($folder);
        }
        return $checks;
    }

    /**
     * @param string $version
     * @return string
     */
    private function checkPHPVersion($version)
    {
        return version_compare(PHP_VERSION, $version, 'ge') ? 'ok' : 'fail';
    }

    /**
     * @param string $extension
     * @return string
     */
    private function checkExtension($extension)
    {
        return extension_loaded($extension) ? 'ok' : 'fail';
    }

    /**
     * @param string $version
     * @return string
     */
    private function checkXHVersion($version)
    {
        return version_compare(CMSIMPLE_XH_VERSION, "CMSimple_XH $version", 'ge') ? 'ok' : 'fail';
    }

    /**
     * @param string $folder
     * @return string
     */
    private function checkWritability($folder)
    {
        return is_writable($folder) ? 'ok' : 'warn';
    }
}
