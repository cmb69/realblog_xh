<?php

/**
 * @copyright 2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class SystemCheck
{
    /**
     * @return string
     */
    public function render()
    {
        $view = new View('system-check');
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
        $phpVersion = '5.3.0';
        $checks[sprintf($ptx['syscheck_phpversion'], $phpVersion)] = $this->checkPHPVersion($phpVersion);
        foreach (array('filter', 'sqlite3') as $extension) {
            $checks[sprintf($ptx['syscheck_extension'], $extension)] = $this->checkExtension($extension);
        }
        $xhVersion = '1.6.3';
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
