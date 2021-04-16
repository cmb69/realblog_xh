<?php

const CMSIMPLE_URL = 'http://example.com/';

const CMSIMPLE_ROOT = '/';

const CMSIMPLE_XH_VERSION = 'CMSimple_XH 1.7.4';

/**
 * @param string $text
 * @param bool $compat
 * @return string
 */
function evaluate_scripting($text, $compat = true) {}

/**
 * @param array $elementClasses
 * @param mixed $initFile
 * @return bool
 */
function init_editor(array $elementClasses = array(), $initFile = false) {}

/**
 * @return string
 */
function plugin_admin_common() {}

/**
 * @param string $main
 * @return string
 */
function print_plugin_admin($main) {}

/**
 * @param string $add
 * @param string $link
 * @param string $target
 * @param string $text
 * @param array  $style
 * @return mixed
 */
function pluginMenu($add = '', $link = '', $target = '', $text = '', array $style = array()) {}

/**
 * @param string $string
 * @return int
 */
function utf8_strlen($string) {}

/**
 * @param string
 * @param int
 * @param int
 * @return string
 */
function utf8_substr($string, $offset, $length = null) {}

/**
 * @param string $string
 * @return string
 */
function XH_hsc($string) {}

/**
 * @param string $type
 * @param string $message
 * @param mixed ...$args
 * @return string
 */
function XH_message($type, $message, ...$args) {}

/**
 * @param int $count
 * @return string
 */
function XH_numberSuffix($count) {}

/**
 * @param string $plugin
 * @param string $label
 * @param string $url
 * @param string $target
 * @return mixed
 */
function XH_registerPluginMenuItem($plugin, $label = null, $url = null, $target = null) {}

/**
 * @param bool $showMain
 * @return void
 */
function XH_registerStandardPluginMenuItems($showMain) {}

/**
 * @param string $str
 * @return string
 */
function XH_rmws($str) {}

/**
 * @param string $pluginName
 * @return bool
 */
function XH_wantsPluginAdministration($pluginName) {}
