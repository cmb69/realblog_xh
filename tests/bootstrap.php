<?php

const CMSIMPLE_XH_VERSION = "1.8";
const CMSIMPLE_URL = "http://example.com/";
const CMSIMPLE_ROOT = "/";
const REALBLOG_VERSION = "3.0beta9";

require_once "./vendor/autoload.php";

require_once "../../cmsimple/classes/CSRFProtection.php";
require_once "../../cmsimple/functions.php";
require_once "../../cmsimple/utf8.php";

require_once "../plib/classes/Response.php";
require_once "../plib/classes/SystemChecker.php";
require_once "../plib/classes/FakeSystemChecker.php";

spl_autoload_register(function (string $className) {
    $parts = explode("\\", $className);
    if ($parts[0] !== "Realblog") {
        return;
    }
    if (count($parts) === 3) {
        $parts[1] = strtolower($parts[1]);
    }
    $filename = implode("/", array_slice($parts, 1));
    if (is_readable("./classes/$filename.php")) {
        include_once "./classes/$filename.php";
    } elseif (is_readable("./tests/$filename.php")) {
        include_once "./tests/$filename.php";
    }
});
