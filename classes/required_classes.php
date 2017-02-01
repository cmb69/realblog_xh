<?php

/**
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

spl_autoload_register(
    function ($class) {
        $parts = explode('\\', $class, 2);
        if ($parts[0] == 'Realblog') {
            include_once dirname(__FILE__) . '/' . $parts[1] . '.php';
        }
    }
);
