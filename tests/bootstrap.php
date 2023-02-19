<?php

const CMSIMPLE_URL = "http://example.com/";

require_once "./vendor/autoload.php";

require_once "../../cmsimple/classes/CSRFProtection.php";
require_once "../../cmsimple/functions.php";

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
    }
});
