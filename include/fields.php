<?php

if (!function_exists('sv') || preg_match('#/plugins/realblog/include/fields.php#i', $_SERVER['SCRIPT_NAME']))
{
	die('no direct access');
}

/* utf-8 marker äöüß */
    define("REALBLOG_ID",0);
    define("REALBLOG_DATE",1);
    define("REALBLOG_STARTDATE",2);
    define("REALBLOG_ENDDATE",3);
    define("REALBLOG_STATUS",4);
    define("REALBLOG_FRONTPAGE",5);
    define("REALBLOG_TITLE",6);
    define("REALBLOG_HEADLINE",7);
    define("REALBLOG_STORY",8);
    define("REALBLOG_RSSFEED",9);
	define("REALBLOG_COMMENTS",10);
?>
