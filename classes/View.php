<?php

/**
 * @copyright 2016-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class View
{
    private $template;

    private $data = array();

    public function __construct($template)
    {
        $this->template = $template;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->escape($this->data[$name]);
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __call($name, array $args)
    {
        return $this->escape(call_user_func_array($this->data[$name], $args));
    }

    protected function text($key)
    {
        global $plugin_tx;

        return $this->escape($plugin_tx['realblog'][$key]);
    }

    protected function plural($key, $count)
    {
        global $plugin_tx;

        $key = $key . XH_numberSuffix($count);
        return $this->escape(sprintf($plugin_tx['realblog'][$key], $count));
    }

    public function render()
    {
        global $pth;

        ob_start();
        include "{$pth['folder']['plugins']}realblog/views/{$this->template}.php";
        return ob_get_clean();
    }

    protected function escape($value)
    {
        if (is_scalar($value)) {
            return XH_hsc($value);
        } else {
            return $value;
        }
    }
}
