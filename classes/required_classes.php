<?php

/**
 * Loads required classes.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Realblog
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Realblog_XH
 */

/**
 * Autoloads a plugin class.
 *
 * @param string $class A class name.
 *
 * @return void
 */
function Realblog_autoload($class)
{
    global $pth;

    $parts = explode('_', $class, 2);
    if ($parts[0] == 'Realblog') {
        include_once $pth['folder']['plugins'] . 'realblog/classes/'
            . $parts[1] . '.php';
    }
}

spl_autoload_register('Realblog_autoload');

?>
