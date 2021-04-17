<?php

/**
 * Copyright 2016-2017 Christoph M. Becker
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

class View
{
    /** @var array<string,mixed> */
    private $data = array();

    /**
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        return $this->escape($this->data[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     * @return string
     */
    public function __call($name, array $args)
    {
        return $this->escape(call_user_func_array($this->data[$name], $args));
    }

    /**
     * @param string $key
     * @param float|int|string $args
     * @return string
     */
    protected function text($key, ...$args)
    {
        global $plugin_tx;

        return sprintf($plugin_tx['realblog'][$key], ...$args);
    }

    /**
     * @param string $key
     * @param int $count
     * @param float|int|string $args
     * @return string
     */
    protected function plural($key, $count, ...$args)
    {
        global $plugin_tx;

        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        return sprintf($plugin_tx['realblog'][$key], ...$args);
    }

    /**
     * @param string $_template
     * @param array<string,mixed>|null $_data
     * @return string
     */
    public function render($_template, array $_data = null)
    {
        global $pth;

        if ($_data !== null) {
            $this->data = $_data;
        }
        ob_start();
        /** @psalm-suppress UnresolvableInclude */
        include "{$pth['folder']['plugins']}realblog/views/{$_template}.php";
        return (string) ob_get_clean();
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function escape($value)
    {
        if (is_scalar($value)) {
            return XH_hsc((string) $value);
        } else {
            return $value;
        }
    }
}
