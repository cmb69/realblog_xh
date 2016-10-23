<?php

/**
 * @copyright 2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class HtmlString
{
    private $value;

    public function __construct($string)
    {
        $this->value = $string;
    }

    public function __toString()
    {
        return $this->value;
    }
}
