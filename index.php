<?php

if (!function_exists('sv') || preg_match('#/plugins/realblog/index.php#i', $_SERVER['SCRIPT_NAME']))
{
	die('no direct access');
}

/* utf8-marker: äöü */
/*
************************************
RealBlog plugin for CMSimple
RealBlog v2.8
released 2014-05-11
Gert Ebersbach - http://www.ge-webdesign.de
------------------------------------
Based on:  AdvancedNews from Jan Kanters - http://www.jat-at-home.be/
Version :  V 1.0.5 GPL
------------------------------------
Credits :  - flatfile database class Copyright 2005 Luke Plant <L.Plant.98@cantab.net>
           - FCKEditor (older versions) and TinyMCE
           - Date Picker (jscalendar) by Copyright (c) Dynarch.com
License :  GNU General Public License, version 2 or later of your choice
************************************

This program is free software; you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation; either version 2 of the
License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program;
if not, see <http://www.gnu.org/licenses>.
*/

@session_start();

define('REALBLOG_VERSION', 'RealBlog 2.8');
$realblog_release = '05/2014';
$realblog_based_on = '<p>based on the Plugin <b>AdvancedNews</b> by <a href="http://www.jat-at-home.be/index.php?CMSimple_plugins">Jan Kanters</a></p><div style="font-family: arial, sans-serif; font-size: 15px; letter-spacing: 0; border: 4px double; padding: 6px 16px; margin: 2px 0 8px 0;">
		<p style="text-align: center;">This Plugin is made for <a href="http://www.cmsimple.org/"><span style="font-weight: 700; padding: 0 6px; ">CMSimple 4.0 &raquo;</span></a> or higher.</p>
		<p style="text-align: center;">Optimized for <span style="font-weight: 700; padding: 0 6px; ">CMSimple 4.4</span> or higher.</p>
		<p style="text-align: center;">Recommended is the current version of CMSimple from <a href="http://www.cmsimple.org/en/?Downloads___CMSimple">cmsimple.org&nbsp;&raquo;</a></p>
		</div>';

/* EVALUATION FUNCTIONS FOR PLUGINS / SCRIPTING */

function evaluate_cmsimple_scripting_crb($__text_crb, $__compat_crb = TRUE) {
	global $output;
    foreach ($GLOBALS as $__name_crb => $__dummy_crb) {global $$__name_crb;}

    $__scope_before_crb = NULL; // just that it exists
    $__scripts_crb = array();
    preg_match_all('~'.$cf['scripting']['regexp'].'~is', $__text_crb, $__scripts_crb);
    if (count($__scripts_crb[1]) > 0) {
        $output = preg_replace('~'.$cf['scripting']['regexp'].'~is', '', $__text_crb);
	if ($__compat_crb) {$__scripts_crb[1] = array_reverse($__scripts_crb[1]);}
        foreach ($__scripts_crb[1] as $__script_crb) {
            if ($__script_crb !== 'hide' && $__script_crb !== 'remove') {
                $__script_crb = preg_replace(
                        array("'&(quot|#34);'i", "'&(amp|#38);'i", "'&(apos|#39);'i", "'&(lt|#60);'i", "'&(gt|#62);'i", "'&(nbsp|#160);'i"),
                        array("\"", "&", "'", "<", ">", " "),
                        $__script_crb);
		$__scope_before_crb = array_keys(get_defined_vars());
                eval($__script_crb);
		$__scope_after_crb = array_keys(get_defined_vars());
		$__diff_crb = array_diff($__scope_after_crb, $__scope_before_crb);
		foreach ($__diff_crb as $__var_crb) {$GLOBALS[$__var_crb] = $$__var_crb;}
		if ($__compat_crb) {break;}
            }
        }
		$eval_script_output = $output;
		$output = '';
		return $eval_script_output;
    }
    return $__text_crb;
}

function evaluate_plugincall_crb($__text_crb) {
    global $u;

    $error = ' <span style="color:#5b0000; font-size:14px;">{{CALL TO:<span style="color:#c10000;">{{%1}}</span> FAILED}}</span> '; //use this for debugging of failed plugin-calls
    $pl_regex = '"{{{RGX:CALL(.*?)}}}"is'; //general CALL-RegEx (Placeholder: "RGX:CALL")
    $pl_calls = array(
	'PLUGIN:' => 'return {{%1}}',
	'HOME:' => 'return trim(\'<a href="?' . $u[0] . '" title="' . urldecode('{{%1}}') . '">' . urldecode('{{%1}}') . '</a>\');',
	'HOME' => 'return trim(\'<a href="?' . $u[0] . '" title="' . urldecode($u[0]) . '">' . urldecode($u[0]) . '</a>\');'
    );
    $fd_calls = array();
    foreach ($pl_calls AS $regex => $call) {
	preg_match_all(str_replace("RGX:CALL", $regex, $pl_regex), $__text_crb, $fd_calls[$regex]); //catch all PL-CALLS
	foreach ($fd_calls[$regex][0] AS $call_nr => $replace) {
	    $call = str_replace("{{%1}}", $fd_calls[$regex][1][$call_nr], $pl_calls[$regex]);
	    $fnct_call = preg_replace('"(?:(?:return)\s)*(.*?)\(.*?\);"is', '$1', $call);
	    $fnct = function_exists($fnct_call) ? TRUE : FALSE; //without object-calls; functions-only!!
	    if ($fnct) {
		preg_match_all("/\\$([a-z_0-9]*)/i", $call, $matches);
		foreach ($matches[1] as $var) {
		    global $$var;
		}
	    }
	    $__text_crb = str_replace($replace,
		    ($fnct
			? eval(str_replace('{{%1}}', $fd_calls[$regex][1][$call_nr], $pl_calls[$regex]))
			: str_replace('{{%1}}', $regex . $fd_calls[$regex][1][$call_nr], $error)),
		    $__text_crb); //replace PL-CALLS (String only!!)
	}
    }
    return $__text_crb;
}

function evaluate_scripting_crb($text, $compat = TRUE) {
    return evaluate_cmsimple_scripting_crb(evaluate_plugincall_crb($text), $compat);
}

/* END EVALUATION FUNCTIONS FOR PLUGINS / SCRIPTING */

/*
********************************************************************************
* This routine does some automatic realblog status updating
* it changes the realblog status automatically from :
*   - ready to publish -> published; when current date is in between start & end date
*   - published -> archived; when current date > end date
* and also generates an up-to-date RSS newsfeed file
********************************************************************************
*/

$rss_path='./';
if (!is_writeable($rss_path . 'realblog_rss_feed.xml') && $adm)
		{
			$o.= '<div class="cmsimplecore_warning" style="text-align: center;"><b>RealBlog:</b> RSS file "./realblog_rss_feed.xml" not writable.</div>';
		}

if (!$adm)
{
	// recover variables from outsite the function
	global $pth, $sn, $plugin_tx, $plugin_cf, $u, $s, $c, $sl, $f, $tx, $cal_format, $hjs;

	// create the site RSS:rdf file
	realblog_export_rssfeed();

	// get plugin name
	$plugin = basename(dirname(__FILE__), "/");

	// set locale time
	setlocale(LC_ALL, $plugin_tx[$plugin]['date_locale']);

	// set general variables for the plugin
	$plugin_images_folder = $pth['folder']['plugins'] . $plugin . "/images/";
	$plugin_include_folder = $pth['folder']['plugins'] . $plugin . "/include/";

	$db_path = $pth['folder']['content'] . 'realblog/';

	$db_name="realblog.txt";

	// include the flatfile database class
	require_once ($plugin_include_folder . "flatfile.php");

	// declare the realblog database fields
	require_once ($plugin_include_folder . "fields.php");

	// connect to the realblog database file
	$db = new Flatfile();
	$db->datadir = $db_path;
	$d = date("d", time());
	$m = date("n", time());
	$y = date("Y", time());
	$today = mktime(NULL, NULL, NULL, $m, $d, $y);

	// Change realblog status from ready for publishing to published when current date is within the publishing period
	$compClause           =NULL;

    if (strtolower($plugin_cf[$plugin]['auto_publish']) == 'true')
	{
		$compClause=new AndWhereClause(new SimpleWhereClause(REALBLOG_STATUS, "<=", 0, INTEGER_COMPARISON),	new AndWhereClause(new SimpleWhereClause(REALBLOG_STARTDATE, "<=", $today), new SimpleWhereClause(REALBLOG_ENDDATE, ">=", $today)));
		$records=$db->selectWhere($db_name, $compClause, -1);

		foreach ($records as $key => $field)
		{
            unset ($realblogitem);
            $realblogitem[REALBLOG_ID]    =$field[REALBLOG_ID];
            $realblogitem[REALBLOG_STATUS]=1;
            $db->updateRowById($db_name, REALBLOG_ID, $realblogitem);
		}
	}

	// Change realblog status from published to archived when publishing period is ended and auto archive is enabled

	if ($plugin_cf['realblog']['auto_archive'] == 'true')
	{

		$compClause=NULL;
		$compClause=new AndWhereClause(new SimpleWhereClause(REALBLOG_STATUS, "<=", 1), new SimpleWhereClause(REALBLOG_ENDDATE, "<", $today, INTEGER_COMPARISON));

		if($plugin_cf['realblog']['entries_order'] == 'desc')
		{
			$records = $db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));
		}
		else
		{
			$records = $db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, ASCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, ASCENDING, INTEGER_COMPARISON)));
		}

		foreach ($records as $key => $field)
		{
			unset ($realblogitem);
			$realblogitem[REALBLOG_ID]    =$field[REALBLOG_ID];
			$realblogitem[REALBLOG_STATUS]=2;
			$db->updateRowById($db_name, REALBLOG_ID, $realblogitem);
		}
	}
    $hjs .= tag('link rel="alternate" type="application/rss+xml" title="' . sitename(). '" href="./realblog_rss_feed.xml"')."\n";
}

/*
********************************************************************************
* Dummy function for categories
********************************************************************************
*/

function rbCat()
{
	return;
}

function CommentsMembersOnly()
{
	global $plugin_cf;
	$plugin_cf['realblog']['comments_form_protected'] = 'true';
}


/*
********************************************************************************
* This function display's the realblog topics status=published
* usage : showrealblog()
********************************************************************************
*/

function showrealblog ($options = NULL,$realBlogCat = 'all')
{
	// recover variables from outsite the function
    global $adm, $pth, $sn, $title, $plugin_tx, $plugin_cf, $u, $s, $c, $sl, $f, $tx, $cal_format, $hjs, $realblogID, $commentschecked, $id, $from_page, $page, $realblog_page;

	// get plugin name
	$plugin=basename(dirname(__FILE__), "/");

	// set locale time
	setlocale(LC_ALL, $plugin_tx[$plugin]['date_locale']);
	$layout = 'blog';
	$includesearch = 'false';
	$arguments    =explode(',', $options);

	if (count($arguments > 0))
	{
		foreach ($arguments as $argument)
		{
			$property = explode('=', $argument);
			switch (strtolower($property[0]))
			{
				case "showsearch":

				if (strtolower($property[1]) === "true" || strtolower($property[1]) === "false" || strtolower($property[1]) === "1" || strtolower($property[1]) === "0")
				{
					switch ($property[1])
					{
						case '0':$includesearch='false';
						break;

						case '1':$includesearch='true';
						break;

						default:$includesearch=strtolower($property[1]);
						break;
					}
				}
				else
				{
					$includesearch='false';
				}
				break;
			}
		}
	}

	// retrieve posted variables
	$realblogID    =isset($_POST['realblogID']) ? $_POST['realblogID'] : @$_GET['realblogID'];
	$page      =isset($_POST['page']) ? $_POST['page'] : @$_GET['page'];
	$realblogaction=isset($_POST['realblogaction']) ? $_POST['realblogaction'] : @$_GET['realblogaction'];
	$realblogYear  =isset($_POST['realblogYear']) ? $_POST['realblogYear'] : @$_GET['realblogYear'];
	$compClause=isset($_POST['compClause']) ? $_POST['compClause'] : @$_GET['compClause'];
	$printrealblog =isset($_POST['printrealblog']) ? $_POST['printrealblog'] : @$_GET['printrealblog'];

	// Date Picker settings

/*
	$monthnames=explode(",", $plugin_tx[$plugin]['month_names']);

	foreach ($monthnames as $key => $value)
	{
		$months=@$months . '"' . $value . '",';
	}

	$months=substr($months, 0, strlen($months) - 1);
	$daynames1=explode(",", @$plugin_tx[$plugin]['weekday_names1']);

	foreach ($daynames1 as $key => $value)
	{
		$days1=@$days1 . '"' . $value . '",';
	}

	$days1=substr($days1, 0, strlen($days1) - 1);
	$daynames2=explode(",", @$plugin_tx[$plugin]['weekday_names2']);

	foreach ($daynames2 as $key => $value)
	{
		$days2=@$days2 . '"' . $value . '",';
	}

	$days2=substr($days2, 0, strlen($days2) - 1);

	// Calendar plugin
//	$hjs.= tag('link rel="stylesheet" type="text/css" media="all" href="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/calendar-system.css"') . "\n";
//	$hjs.='<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/calendar.js"></script>' . "\n";

	// Set jscalendar to default (en) if current website language isn't available
	$hjs.=(is_file($pth['folder']['plugins'] . $plugin . "/jscalendar/lang/calendar-" . $sl . ".js")) ? '<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/lang/calendar-' . $sl . '.js"></script>' . "\n"  : '<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/lang/calendar-en.js"></script>' . "\n";

// End modify JAT 07/11/2005
	$hjs.='<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/calendar-setup.js"></script>' . "\n";
	// End added JAT 29/10/2005 //
*/

	// set general variables for the plugin
	$plugin_images_folder=$pth['folder']['plugins'] . $plugin . "/images/";
	$plugin_include_folder=$pth['folder']['plugins'] . $plugin . "/include/";

	$db_path = $pth['folder']['content'] . 'realblog/';
	$db_name="realblog.txt";

	// Show / hide search block
	$hjs.= "\n" . '<script type="text/javascript">' . "\n" . 'function realblog_showSearch()
	{
	if (document.getElementById("searchblock").style.display == "none")
		{
			var mytitle="' . $plugin_tx[$plugin]['tooltip_hidesearch'] . '";
			document.getElementById("btn_img").title=mytitle;
			document.getElementById("btn_img").src="' . $plugin_images_folder . 'btn_collapse.gif";
			document.getElementById("searchblock").style.display = "block";
		}
		else
		{
			var mytitle="' . $plugin_tx[$plugin]['tooltip_showsearch'] . '";
			document.getElementById("btn_img").title=mytitle;
			document.getElementById("btn_img").src="' . $plugin_images_folder . 'btn_expand.gif";
			document.getElementById("searchblock").style.display = "none";
		}
	}
	</script>' . "\n";

	// include the flatfile database class
	require_once ($plugin_include_folder . "flatfile.php");

	// declare the realblog database fields
	require_once ($plugin_include_folder . "fields.php");

	// connect to the realblog database file
	$db = new Flatfile();
	$db->datadir=$db_path;

	if ($realblogaction != "view")
	{
		$compClause     =new SimpleWhereClause(REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON);
		global $pth, $sn, $plugin_tx, $plugin_cf, $u, $s, $c, $sl, $f, $tx;

		// Create the appropriate date format for the date picker
		$my_date_format1=explode('/', $plugin_tx[$plugin]['date_format']);

		if (count($my_date_format1) > 1)
		{
			$date_separator1="/";
		}
		else
		{
			$my_date_format1=explode('.', $plugin_tx[$plugin]['date_format']);

			if (count($my_date_format1) > 1)
			{
				$date_separator1=".";
			}
			else
			{
				$my_date_format1=explode('-', $plugin_tx[$plugin]['date_format']);

				if (count($my_date_format1) > 1)
				{
					$date_separator1="-";
				}
			}
		}

		for ($aCounter1=0; $aCounter1 <= 2; $aCounter1++)
		{
			switch ($my_date_format1[$aCounter1])
			{
				case 'd':$cal_date_format1[$aCounter1]="%d";
				break;

				case 'm':
				$cal_date_format1[$aCounter1]="%m";
				break;

				case 'y':$cal_date_format1[$aCounter1]="%y";
				break;

				case 'Y':$cal_date_format1[$aCounter1]="%Y";
				break;
			}
		}

		foreach ($cal_date_format1 as $key => $value)
		{
			$cal_format.=($key < count($my_date_format1) - 1) ? $value . $date_separator1 : $value;
		}

		// Build the search block
		if (strtolower($includesearch) == 'true')
		{
			$t ="\n<div>\n<form name=\"realblogsearch\" method=\"post\" action=\"" . $sn . "?" . $u[$s] . "\">";
			$t.="\n<div id=\"enablesearch\">\n<a href=\"javascript:realblog_showSearch()\">\n" . tag('img id="btn_img" alt="searchbuttonimg" src="' . $plugin_images_folder . 'btn_expand.gif" title="' . $plugin_tx[$plugin]['tooltip_showsearch'] . '" style="border: 0;"') . "</a>\n&nbsp;<b>" . $tx['search']['button'] . "</b>\n</div>\n";
			$t.="\n<div id=\"searchblock\" style=\"display:none\">\n";
			$t.= tag('input type="hidden" name="realblogaction" value="search"');
			$t.= tag('input type="hidden" name="realblogYear" value="' . $realblogYear . '"');
			$t.='<p class="realblog_search_hint">' . $plugin_tx['realblog']['search_hint'] . '</p>';
			$t.="\n" . '<table style="width: 100%;">' . "\n";
			$t.="<tr>\n" . '<td style="width: 30%;" class="realblog_search_text">' . $plugin_tx[$plugin]['title_label'] . ' ' . $plugin_tx['realblog']['search_contains'] . ':' . "\n</td>\n<td>\n<select name=\"title_operator\" style=\"visibility: hidden; width: 0;\">\n<option value=\"2\" selected=\"selected\">" . $plugin_tx[$plugin]['search_contains'] . "</option>\n</select>\n" . tag('input type="text" name="realblog_title" size="35" class="realblog_search_input" maxlength="64"') . "\n</td>\n</tr>\n";
			$t.="<tr>\n" . '<td style="width: 30%;">&nbsp;</td>' . "\n" . '<td>' . "\n" . '&nbsp;&nbsp;&nbsp;' .
			tag('input id="operator_2a" type="radio" name="operator_2" value="AND"') . '&nbsp;' . $plugin_tx[$plugin]['search_and'] . "&nbsp;&nbsp;&nbsp;" .
			tag('input id="operator_2b" type="radio" name="operator_2" value="OR" checked="checked"') . '&nbsp;' . $plugin_tx[$plugin]['search_or'] . "</td>\n</tr>\n";
			$t.="<tr>\n" . '<td style="width: 30%;" class="realblog_search_text">' . $plugin_tx[$plugin]['story_label'] . ' ' . $plugin_tx['realblog']['search_contains'] . ':' . "</td><td><select name=\"story_operator\" style=\"visibility: hidden; width: 0;\"><option value=\"2\" selected=\"selected\">" . $plugin_tx[$plugin]['search_contains'] . "</option>\n</select>\n" .
			tag('input type="text" name="realblog_story" size="35" class="realblog_search_input" maxlength="64"') . "</td></tr>\n";
			$t.="<tr>\n<td colspan=\"2\">&nbsp;</td></tr>\n";
			$t.="<tr>\n<td colspan=\"2\"" . ' style="text-align: center;">' .
			tag('input type="submit" name="send" value="' . $tx['search']['button'] . '"') . "</td></tr>\n";
			$t.="</table>\n</div>\n";
			$t.="</form>\n";
			$t.="</div>\n";
		}

		if ($realblogaction == "search")
		{
			$compRealblogClause=new SimpleWhereClause(REALBLOG_STATUS, '=', 1, INTEGER_COMPARISON);

			if (!empty($_REQUEST['realblog_from_date']))
			{
				$compClauseDate1=new SimpleWhereClause(REALBLOG_DATE, $_REQUEST[date_operator_1], make_timestamp_dates1($_REQUEST[realblog_from_date]));
			}

			if (!empty($_REQUEST['realblog_to_date']))
			{
				$compClauseDate2=new SimpleWhereClause(REALBLOG_DATE, $_REQUEST[date_operator_2], make_timestamp_dates1($_REQUEST[realblog_to_date]));
			}

			if (!empty($_REQUEST['realblog_title']))
			{
			$compClauseTitle=new LikeWhereClause(REALBLOG_TITLE, $_REQUEST[realblog_title], $_REQUEST[title_operator]);
			}

			if (!empty($_REQUEST['realblog_story']))
			{
				$compClauseStory=new LikeWhereClause(REALBLOG_STORY, $_REQUEST['realblog_story'], $_REQUEST['story_operator']);
			}

			// [only from_date]
			if (!empty($compClauseDate1) && empty($compClauseDate2) && empty($compClauseTitle) && empty($compClauseStory))
			{
				$compClause=$compClauseDate1;
			}

			// [only to_date]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && empty($compClauseStory))
			{
				$compClause=$compClauseDate2;
			}

			// [from_date & to_date]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && empty($compClauseStory))
			{
			$compClause=new AndWhereClause($compClauseDate1, $compClauseDate2);
			}

			// [only title]
			if (empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				$compClause=$compClauseTitle;
			}

			// [from_date & title]
			if (!empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClauseDate1, $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate1, $compClauseTitle);
					break;
				}
			}

			// [to_date & title]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClauseDate2, $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate2, $compClauseTitle);
					break;
				}
			}

			// [from_date, to_date & title]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseTitle);
					break;
				}
			}

			// [only story]
			if (empty($compClauseDate1) && empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=$compClauseStory;
			}

			// [from_date & story]
			if (!empty($compClauseDate1) && empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClauseDate1, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate1, $compClauseStory);
					break;
				}
			}

			// [to_date & story]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND": $compClause=new AndWhereClause($compClauseDate2, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate2, $compClauseStory);
					break;
				}
			}

			// [from_date, to_date & story]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseStory);
					break;
				}
			}

			// [title & story]
			if (empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClauseTitle, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseTitle, $compClauseStory);
					break;
				}
			}

			// [from_date, title & story specified
			if (!empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=$compClauseDate1;

				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseTitle);
					break;

					case "OR":
					$compClause=new OrWhereClause($compClause, $compClauseTitle);
					break;
				}

				switch ($_REQUEST[operator_2])
				{
				case "AND":$compClause=new AndWhereClause($compClause, $compClauseStory);
				break;

				case "OR":
					$compClause=new OrWhereClause($compClause, $compClauseStory);
					break;
				}
			}

			// [to_date, title & story]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=$compClauseDate2;

				switch ($_REQUEST[operator_1])
				{
				case "AND": $compClause=new AndWhereClause($compClause, $compClauseTitle);
				break;

				case "OR":$compClause=new OrWhereClause($compClause, $compClauseTitle);
				break;
				}

				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClause, $compClauseStory);
					break;
				}
			}

			// [from_date, to_date, title & story]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=new AndWhereClause($compClauseDate1, $compClauseDate2);

				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause($compClause, $compClauseTitle);
					break;
				}

				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClause, $compClauseStory);
					break;
				}
			}
		}

		if ($realblogaction == "search")
		{
			$plugin_cf['realblog']['entries_per_page'] = '0';
//			$compClause=serialize($compClause);
			session_write_close();

			if (isset($compClause))
			{
				$compClause=new AndWhereClause($compRealblogClause, $compClause);
			}
			else
			{
				unset ($realblogaction);
			}

			if($plugin_cf['realblog']['entries_order'] == 'desc')
			{
				$records=$db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));
			}
			else
			{
				$records=$db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, ASCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, ASCENDING, INTEGER_COMPARISON)));
			}

			$numberOfSearchResults=$records;

			foreach($numberOfSearchResults as $searchresults)
			{
				if(strstr($searchresults[8],'|' . $realBlogCat . '|'))
				{
					$numberOfSearchResults[] = '';
				}
			}

			if($realBlogCat != 'all')
			{
				$db_search_records = count($numberOfSearchResults) - count($records);
			}
			else
			{
				$db_search_records = count($numberOfSearchResults);
			}

			$t.= '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>"' . $_REQUEST['realblog_story'] . '"</b></p>';

			$t.='<p>' . $plugin_tx['realblog']['search_result'] . '<b> ' . $db_search_records . '</b></p>';
			$t.='<p><a href="' . preg_replace('/\&.*\z/', '', $_SERVER['REQUEST_URI']) . '"><b>' . $plugin_tx['realblog']['search_show_all'] . '</b></a></p>' . tag('br');
		}
		else
		{
			// Select all realblog items from the DB
			// if (isset($compClause)) { $compClause=unserialize($compClause); }

			if (empty($compClause)) { $compClause=$compRealblogClause; }

			if($plugin_cf['realblog']['entries_order'] == 'desc')
			{
				$records=$db->selectWhere($db_name, $compClause, -1,array(
				new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
				new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));
			}
			else
			{
				$records=$db->selectWhere($db_name, $compClause, -1,array(
				new OrderBy(REALBLOG_DATE, ASCENDING, INTEGER_COMPARISON),
				new OrderBy(REALBLOG_ID, ASCENDING, INTEGER_COMPARISON)));
			}
		}

		foreach($records as $catRecordsTemp)
		{
			if(strpos($catRecordsTemp[7],'|' . $realBlogCat . '|') || strpos($catRecordsTemp[8],'|' . $realBlogCat . '|') || $realBlogCat == 'all')
			{
				$catRecords[] = $catRecordsTemp;
			}
		}

		$records = $catRecords;

		switch (strtolower($layout))
		{
			case "blog":
			// Set limit of record in the blog
			$page_record_limit=$plugin_cf[$plugin]['entries_per_page'];

			if ($page_record_limit <= 0)
			{
				$page_record_limit=10;
			}

			if ($page_record_limit >= 32)
			{
				$page_record_limit=32;
			}

			// Count the total records

			$db_total_records = count($records);

			// Calculate the number of possible pages
			$page_total=($db_total_records % $page_record_limit == 0) ? ((int)$db_total_records / $page_record_limit) : ((int)($db_total_records / $page_record_limit) + 1);

			// Calculate table paging
			@$t.="\n<div class=\"realblog_show_box\">\n";

			if ($page > $page_total)
			{
				$page=1;
			}

			if ($page == "" || $page <= 0 || $page == 1)
			{
				$start_index=0;
				$page       =1;
			}
			else
			{
				$start_index=(($page - 1) * ($page_record_limit));
			}

			// Display realblog items overview

			// Blog overview paging
			$mysearch="";

			if ($db_total_records > 0 && $page_total > 1)
			{
				if ($page_total > $page)
				{
					$next=$page + 1;
					$back=($page > 1) ? ($next - 2) : "1";
				}
				else
				{
					$next=$page_total;
					$back=$page_total - 1;
				}

			}

			$tmp=($db_total_records > 0) ? "" . $plugin_tx[$plugin]['page_label'] . " : " . "\n<a href=\"" . $sn . "?" . $u[$s] . "&amp;page=" . @$back . $mysearch . "\" title=\"" . $plugin_tx[$plugin]['tooltip_previous'] . "\">" . tag("img src=\"" . $plugin_images_folder . "btn_previous.gif\" alt=\"previous_img\"") . "</a>&nbsp;\n" . $page . " / " . $page_total : "";

			$tmp.="&nbsp;\n<a href=\"" . $sn . "?" . $u[$s] . "&amp;page=" . @$next . $mysearch . "\" title=\"" . $plugin_tx[$plugin]['tooltip_next'] . "\">" .
tag("img src=\"" . $plugin_images_folder . "btn_next.gif\" class=\"btn_prev_next\" alt=\"next_img\"") . "</a>\n";

			if ($db_total_records > 0 && $page_total > 1)
			{
				$t.="\n<div class=\"realblog_table_paging\">\n";

				for ($tt=1; $tt <= $page_total; $tt++)
				{
					$separator = ($tt < $page_total) ? " " : "";
					$t.="<a href=\"" . $sn . "?" . $u[$s] . "&amp;page=" . $tt . $mysearch . "\" title=\"" . $plugin_tx[$plugin]['page_label'] . " " . $tt . "\">[" . $tt . "]</a>" . $separator;
				}

				$t.="\n</div>\n";
			}

			if(!@$_REQUEST['realblog_story'] && $plugin_cf['realblog']['show_numberof_entries_top'] == 'true')
			{
				$t.="<div class=\"realblog_db_info\">\n" . $plugin_tx[$plugin]['record_count'] . " : " . $db_total_records . "\n</div>\n";
			}

			if ($db_total_records > 0 && $page_total > 1)
			{
				$t.="<div class=\"realblog_page_info\">\n" . $tmp . "</div>";
			}
			$t.="\n<div style=\"clear:both;\"></div>";

			// Display table header
			$t.="\n\n<div id=\"realblog_entries_preview\">\n";

			$end_index=$page * $page_record_limit - 1;

			// Display table lines
			for ($record_index=$start_index; $record_index <= $end_index; $record_index++)
			{

				//$color = $record_index % 2 ? "#cccccc" : "#99cccc";

				if ($record_index > $db_total_records - 1)
				{
					$t.="";
				}
				else
				{
					$field=$records[$record_index];

					if(strstr($field[REALBLOG_HEADLINE],'|' . $realBlogCat . '|') || strstr($field[REALBLOG_STORY],'|' . $realBlogCat . '|') || $realBlogCat == 'all' || ($realblogaction == "search" && strstr($field[REALBLOG_H],'|' . $realBlogCat . '|')))
					{
						if($plugin_cf['realblog']['teaser_multicolumns'] == 'true')
						{
							$t.="\n<div class=\"realblog_single_entry_preview\">\n";
							$t.="\n<div class=\"realblog_single_entry_preview_in\">\n";
						}

						$t.= "<h4>";

						if($field[REALBLOG_STORY] != '' || $adm)
						{
							$t.= "<a href=\"" . $sn . "?" . $u[$s] . "&amp;" . str_replace(' ', '_', $field[REALBLOG_TITLE]) . "&amp;realblogaction=view&amp;realblogID=" . $field[REALBLOG_ID] . "&amp;page=" . $page . "\" title=\"" . $plugin_tx[$plugin]["tooltip_view"] . "\" >";
						}

						$t.= $field[REALBLOG_TITLE];

						if($field[REALBLOG_STORY] != '' || $adm)
						{
							$t.='</a>';
						}

						$t.='</h4>' . "\n";

						$t.="\n<div class=\"realblog_show_date\">\n" . strftime($plugin_tx[$plugin]['display_date_format'], $field[REALBLOG_DATE]) . "\n</div>\n";

						$t.="\n<div class=\"realblog_show_story\">\n";

						$t.=evaluate_scripting_crb($field[REALBLOG_HEADLINE]);

						if($plugin_cf['realblog']['show_read_more_link'] == 'true' && $field[REALBLOG_STORY] != '')
						{
							$t.="\n".'<div class="realblog_entry_footer">'."\n";

							// shows number of comments in entries overview - GE 2010-12

							if (function_exists('comments_nr') && $plugin_cf['realblog']['comments_function'] == 'true' && $field[REALBLOG_COMMENTS])
							{
								$realblog_comments_id = 'comments'.$field[REALBLOG_ID];
								$t.= '<p class="realblog_number_of_comments">' . comments_nr($realblog_comments_id) . '</p>' . "\n";
							}


							$t.='<p class="realblog_read_more">' . "<a href=\"" . $sn . "?" . $u[$s] . "&amp;" . str_replace(' ', '_', $field[REALBLOG_TITLE]) . "&amp;realblogaction=view&amp;realblogID=" . $field[REALBLOG_ID] . "&amp;page=" . $page . "\" title=\"" . $plugin_tx[$plugin]["tooltip_view"] . "\" >" . $plugin_tx[$plugin]['read_more'] . "</a></p>\n</div>\n";
						}

						$t.= '<div style="clear: both;"></div>' . "\n</div>\n";
						if($plugin_cf['realblog']['teaser_multicolumns'] == 'true')
						{
							$t.="</div>\n</div>\n";
						}
					}
				}
			}


			$t.= '<div style="clear: both;"></div>' . "\n</div>\n";

			// Blog overview paging

			if(!@$_REQUEST['realblog_story'] != '' && $plugin_cf['realblog']['show_numberof_entries_bottom'] == 'true')
			{
				$t.="\n<div class=\"realblog_db_info\">\n" . $plugin_tx[$plugin]['record_count'] . " : " . $db_total_records . "\n</div>\n";
			}

			if ($db_total_records > 0 && $page_total > 1)
			{
				$t.="\n<div class=\"realblog_page_info\">\n" . $tmp . "</div>\n";
			}

			$mysearch="";

			if ($db_total_records > 0 && $page_total > 1)
			{
				if ($page_total > $page)
				{
					$next=$page + 1;
					$back=($page > 1) ? ($next - 2) : "1";
				}
				else
				{
					$next=$page_total;
					$back=$page_total - 1;
				}

				$t.="\n<div class=\"realblog_table_paging\">\n";

				for ($tt=1; $tt <= $page_total; $tt++)
				{
					$separator = ($tt < $page_total) ? " " : "";
					$t.="<a href=\"" . $sn . "?" . $u[$s] . "&amp;page=" . $tt . $mysearch . "\" title=\"" . $plugin_tx[$plugin]['page_label'] . " " . $tt . "\">[" . $tt . "]</a>" . $separator;
				}

				$t.="</div>";
			}

			$t.="</div>";
			break;
		}
	}
	else
	{

		// Display the realblogitem for the given ID
		$record=$db->selectUnique($db_name, REALBLOG_ID, $realblogID);

		// Set the return page, based on the caling page
		if ($from_page == '' || empty($from_page))
		{
			$from_page=1;
		}

		$return_page=($from_page == $u[$s]) ? $u[$s] : $from_page;

		if (count($record) > 0)
		{

			// Show selected entry (realblog above entry)
			$t ="\n<div class=\"realblog_show_box\">\n";

			// Redirect back to realblog overview (realblog, above entry) - GE 2010 - 11
			global $su, $sn, $s, $plugin_cf;
			$t.= "\n".'<div class="realblog_buttons">' . "\n" . '<span class="realblog_button"><a href="' . $sn . '?' . $su . '&amp;page=' . $page . '">' .$plugin_tx[$plugin]['blog_back'] . '</a></span>';

			// "edit comments" button (realblog, above entry) - GE 2010 - 11
			if (function_exists('comments') && $plugin_cf['realblog']['comments_function'] == 'true' && $adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&comments&admin=plugin_main&action=plugin_text&selected=comments' . $realblogID . '.txt">' . $plugin_tx['realblog']['comment_edit'] . '</a></span>';
			}

			// "edit entry" button (realblog, above entry)
			if($adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&realblog&admin=plugin_main&action=modify_realblog&amp;realblogID=' . $realblogID . '">' . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
			}

			$t.='<div style="clear: both;"></div>';

			$title.= locator() . ' - ' . $record[REALBLOG_TITLE];
			$t.= "\n".'</div>'."\n";
			$t.="<h4>" . $record[REALBLOG_TITLE] . "</h4>";
			$t.="\n<div class=\"realblog_show_date\">\n" . strftime($plugin_tx[$plugin]['display_date_format'], $record[REALBLOG_DATE]) . "\n</div>\n";
			$t.="\n<div class=\"realblog_show_story_entry\">\n" . stripslashes(evaluate_scripting_crb($record[REALBLOG_STORY])) . "\n</div>\n";
			$t.="</div>\n";
			$t.="\n<div>&nbsp;</div>\n";

			if (isset($printrealblog))
			{
				$tt="<html><head><title>" . sitename() . " - " . str_replace('_', ' ', $u[$s]) . "</title>";
				$tt.= tag('link rel="stylesheet" href="' . $pth['folder']['plugins'] . $plugin . '/css/stylesheet.css" type="text/css">');
				$tt.="</head><body>";
				$tt.=$t;
				$tt.="\n<div align=\"center\"\n><a href=javascript:void(0); onclick='window.close()'>" . $plugin_tx[$plugin][window_close] . "</a>&nbsp;&nbsp;&nbsp;\n<a href=javascript:void(0); onclick='window.print()'>" . $plugin_tx[$plugin][window_print] . "</a>\n</div>\n";
				$tt.='</body></html>';
				$t.="<script language='javascript' type='text/javascript'> var printinfo = '" . addslashes($tt) . "';
				win = window.open('', 'printversion', 'toolbar = 0, status = 0, width=600',scrollbars=1);
				win.document.write(printinfo);
				win.document.close();
				</script>";
			}

			// Redirect back to realblog overview (realblog, below entry) - GE 2010 - 11
			global $su, $sn, $s, $page, $plugin_cf;
			$t.= "\n".'<div class="realblog_buttons">' . "\n" . '<span class="realblog_button"><a href="' . $sn . '?' . $su . '&amp;page=' . $page . '">' .$plugin_tx[$plugin]['blog_back'] . '</a></span>';

			// "edit comments" button (realblog, below entry) - GE 2010 - 11
			if (function_exists('comments') && $plugin_cf['realblog']['comments_function'] == 'true' && $adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&comments&admin=plugin_main&action=plugin_text&selected=comments' . $realblogID . '.txt">' . $plugin_tx['realblog']['comment_edit'] . '</a></span>';
			}

			// "edit entry" button (realblog, below entry)
			if($adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&realblog&admin=plugin_main&action=modify_realblog&amp;realblogID=' . $realblogID . '">' . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
			}

			$t.='<div style="clear: both;"></div>';

			$t.= "\n".'</div>'."\n";

			// output comments in RealBlog - GE 2010 - 11
$record[REALBLOG_COMMENTS];

			if (function_exists('comments') && $plugin_cf['realblog']['comments_function'] == 'true' && $record[REALBLOG_COMMENTS] == 'on')
			{
				$realblog_comments_id = 'comments'.$realblogID;
				if($plugin_cf['realblog']['comments_form_protected'] == 'true')
				{
					$t.= comments($realblog_comments_id,'protected');
				}
				else
				{
					$t.= comments($realblog_comments_id);
				}
			}
		}
	}
	$t.="\n<div class=\"realblog_credit\">\nPowered by <a href=\"http://www.ge-webdesign.de/cmsimplerealblog/?Demo_und_WebLog\">CMSimpleRealBlog</a>\n</div>\n";

	$c[$s]="";
	unset ($realblogaction);
	unset ($compClause);
	return ($t);
}


/*
********************************************************************************
* This function display's the archived realblog topics in archiv-view
* usage : showrealblogarchive()
********************************************************************************
*/

function showrealblogarchive ($options = NULL)
{
	// recover variables from outsite the function
    global $adm, $pth, $sn, $title, $plugin_tx, $plugin_cf, $u, $s, $c, $sl, $f, $tx, $cal_format, $hjs, $realblogID, $commentschecked, $id, $from_page, $page;

	// get plugin name
	$plugin=basename(dirname(__FILE__), "/");

	// set locale time
	setlocale(LC_ALL, $plugin_tx[$plugin]['date_locale']);
	$layout = 'archive';
	$includesearch = 'false';
	$arguments    =explode(',', $options);

	if (count($arguments > 0))
	{
		foreach ($arguments as $argument)
		{
			$property = explode('=', $argument);
			switch (strtolower($property[0]))
			{
				case "showsearch":

				if (strtolower($property[1]) === "true" || strtolower($property[1]) === "false" || strtolower($property[1]) === "1" || strtolower($property[1]) === "0")
				{
					switch ($property[1])
					{
						case '0':$includesearch='false';
						break;

						case '1':$includesearch='true';
						break;

						default:$includesearch=strtolower($property[1]);
						break;
					}
				}
				else
				{
					$includesearch='false';
				}
				break;
			}
		}
	}

	// retrieve posted variables
	$realblogID    =isset($_POST['realblogID']) ? $_POST['realblogID'] : @$_GET['realblogID'];
	$page      =isset($_POST['page']) ? $_POST['page'] : @$_GET['page'];
	$realblogaction=isset($_POST['realblogaction']) ? $_POST['realblogaction'] : @$_GET['realblogaction'];
	$realblogYear  =isset($_POST['realblogYear']) ? $_POST['realblogYear'] : @$_GET['realblogYear'];
	$compClause=isset($_POST['compClause']) ? $_POST['compClause'] : @$_GET['compClause'];
	$printrealblog =isset($_POST['printrealblog']) ? $_POST['printrealblog'] : @$_GET['printrealblog'];

/*
	// Date Picker settings
	$monthnames=explode(",", $plugin_tx[$plugin]['month_names']);

	foreach ($monthnames as $key => $value)
	{
		$months=@$months . '"' . $value . '",';
	}

	$months=substr($months, 0, strlen($months) - 1);
	$daynames1=explode(",", @$plugin_tx[$plugin]['weekday_names1']);

	foreach ($daynames1 as $key => $value)
	{
		$days1=@$days1 . '"' . $value . '",';
	}

	$days1=substr($days1, 0, strlen($days1) - 1);
	$daynames2=explode(",", @$plugin_tx[$plugin]['weekday_names2']);

	foreach ($daynames2 as $key => $value)
	{
		$days2=@$days2 . '"' . $value . '",';
	}

	$days2=substr($days2, 0, strlen($days2) - 1);

	// Calendar plugin
//	$hjs.= tag('link rel="stylesheet" type="text/css" media="all" href="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/calendar-system.css"') . "\n";
//	$hjs.='<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/calendar.js"></script>' . "\n";

	// Set jscalendar to default (en) if current website language isn't available
	$hjs.=(is_file($pth['folder']['plugins'] . $plugin . "/jscalendar/lang/calendar-" . $sl . ".js")) ? '<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/lang/calendar-' . $sl . '.js"></script>' . "\n"  : '<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/lang/calendar-en.js"></script>' . "\n";

	// End modify JAT 07/11/2005
	$hjs.='<script type="text/javascript" src="' . $pth['folder']['plugins'] . $plugin . '/jscalendar/calendar-setup.js"></script>' . "\n";
	// End added JAT 29/10/2005 //

*/

	// set general variables for the plugin
	$plugin_images_folder=$pth['folder']['plugins'] . $plugin . "/images/";
	$plugin_include_folder=$pth['folder']['plugins'] . $plugin . "/include/";

	$db_path = $pth['folder']['content'] . 'realblog/';
	$db_name="realblog.txt";

	// Show / hide search block
	$hjs.= "\n" . '<script type="text/javascript">' . "\n" . 'function realblog_showSearch()
	{
	if (document.getElementById("searchblock").style.display == "none")
		{
			var mytitle="' . $plugin_tx[$plugin]['tooltip_hidesearch'] . '";
			document.getElementById("btn_img").title=mytitle;
			document.getElementById("btn_img").src="' . $plugin_images_folder . 'btn_collapse.gif";
			document.getElementById("searchblock").style.display = "block";
		}
		else
		{
			var mytitle="' . $plugin_tx[$plugin]['tooltip_showsearch'] . '";
			document.getElementById("btn_img").title=mytitle;
			document.getElementById("btn_img").src="' . $plugin_images_folder . 'btn_expand.gif";
			document.getElementById("searchblock").style.display = "none";
		}
	}
	</script>' . "\n";

	// include the flatfile database class
	require_once ($plugin_include_folder . "flatfile.php");

	// declare the realblog database fields
	require_once ($plugin_include_folder . "fields.php");

	// connect to the realblog database file
	$db = new Flatfile();
	$db->datadir=$db_path;

	if ($realblogaction != "view")
	{
		$compClause     =new SimpleWhereClause(REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON);
		global $pth, $sn, $plugin_tx, $plugin_cf, $u, $s, $c, $sl, $f, $tx;

		// Create the appropriate date format for the date picker
		$my_date_format1=explode('/', $plugin_tx[$plugin]['date_format']);

		if (count($my_date_format1) > 1)
		{
			$date_separator1="/";
		}
		else
		{
			$my_date_format1=explode('.', $plugin_tx[$plugin]['date_format']);

			if (count($my_date_format1) > 1)
			{
				$date_separator1=".";
			}
			else
			{
				$my_date_format1=explode('-', $plugin_tx[$plugin]['date_format']);

				if (count($my_date_format1) > 1)
				{
					$date_separator1="-";
				}
			}
		}

		for ($aCounter1=0; $aCounter1 <= 2; $aCounter1++)
		{
			switch ($my_date_format1[$aCounter1])
			{
				case 'd':$cal_date_format1[$aCounter1]="%d";
				break;

				case 'm':
				$cal_date_format1[$aCounter1]="%m";
				break;

				case 'y':$cal_date_format1[$aCounter1]="%y";
				break;

				case 'Y':$cal_date_format1[$aCounter1]="%Y";
				break;
			}
		}

		foreach ($cal_date_format1 as $key => $value)
		{
			$cal_format.=($key < count($my_date_format1) - 1) ? $value . $date_separator1 : $value;
		}

		// Build the search block
		if (strtolower($includesearch) == 'true')
		{
			$t ="\n<div>\n<form name=\"realblogsearch\" method=\"post\" action=\"" . $sn . "?" . $u[$s] . "\">";
			$t.="\n<div id=\"enablesearch\">\n<a href=\"javascript:realblog_showSearch()\">\n" . tag('img id="btn_img" alt="searchbuttonimg" src="' . $plugin_images_folder . 'btn_expand.gif" title="' . $plugin_tx[$plugin]['tooltip_showsearch'] . '" style="border: 0;"') . "</a>\n&nbsp;<b>" . $tx['search']['button'] . "</b>\n</div>\n";
			$t.="\n<div id=\"searchblock\" style=\"display:none\">\n";
			$t.= tag('input type="hidden" name="realblogaction" value="search"');
			$t.= tag('input type="hidden" name="realblogYear" value="' . $realblogYear . '"');
			$t.='<p class="realblog_search_hint">' . $plugin_tx['realblog']['search_hint'] . '</p>';
			$t.="\n" . '<table style="width: 100%;">' . "\n";
			$t.="<tr>\n" . '<td style="width: 30%;" class="realblog_search_text">' . $plugin_tx[$plugin]['title_label'] . ' ' . $plugin_tx['realblog']['search_contains'] . ':' . "\n</td>\n<td>\n<select name=\"title_operator\" style=\"visibility: hidden; width: 0;\">\n<option value=\"2\" selected=\"selected\">" . $plugin_tx[$plugin]['search_contains'] . "</option>\n</select>\n" . tag('input type="text" name="realblog_title" size="35" class="realblog_search_input" maxlength="64"') . "\n</td>\n</tr>\n";
			$t.="<tr>\n" . '<td style="width: 30%;"></td>' . "\n" . '<td>' . "\n" . '&nbsp;&nbsp;&nbsp;' .
			tag('input id="operator_2a" type="radio" name="operator_2" value="AND"') . '&nbsp;' . $plugin_tx[$plugin]['search_and'] . "&nbsp;&nbsp;&nbsp;" .
			tag('input id="operator_2b" type="radio" name="operator_2" value="OR" checked="checked"') . '&nbsp;' .  $plugin_tx[$plugin]['search_or'] . "</td>\n</tr>\n";
			$t.="<tr>\n" . '<td style="width: 30%;" class="realblog_search_text">' . $plugin_tx[$plugin]['story_label'] . ' ' . $plugin_tx['realblog']['search_contains'] . ':' . "</td><td><select name=\"story_operator\" style=\"visibility: hidden; width: 0;\"><option value=\"2\" selected=\"selected\">" . $plugin_tx[$plugin]['search_contains'] . "</option>\n</select>\n" .
			tag('input type="text" name="realblog_story" size="35" class="realblog_search_input" maxlength="64"') . "</td></tr>\n";
			$t.="<tr>\n<td colspan=\"2\">&nbsp;</td></tr>\n";
			$t.="<tr>\n<td colspan=\"2\"" . ' style="text-align: center;">' .
			tag('input type="submit" name="send" value="' . $tx['search']['button'] . '"') . "</td></tr>\n";
			$t.="</table>\n</div>\n";
			$t.="</form>\n";
			$t.="</div>\n";
		}

		if ($realblogaction == "search")
		{
			$compArchiveClause=new SimpleWhereClause(REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON);

			if (!empty($_REQUEST[realblog_from_date]))
			{
				$compClauseDate1=new SimpleWhereClause(REALBLOG_DATE, $_REQUEST[date_operator_1], make_timestamp_dates1($_REQUEST[realblog_from_date]));
			}

			if (!empty($_REQUEST[realblog_to_date]))
			{
				$compClauseDate2=new SimpleWhereClause(REALBLOG_DATE, $_REQUEST[date_operator_2], make_timestamp_dates1($_REQUEST[realblog_to_date]));
			}

			if (!empty($_REQUEST[realblog_title]))
			{
			$compClauseTitle=new LikeWhereClause(REALBLOG_TITLE, $_REQUEST[realblog_title], $_REQUEST[title_operator]);
			}

			if (!empty($_REQUEST[realblog_story]))
			{
				$compClauseStory=new LikeWhereClause(REALBLOG_STORY, $_REQUEST[realblog_story], $_REQUEST[story_operator]);
			}

			// [only from_date]
			if (!empty($compClauseDate1) && empty($compClauseDate2) && empty($compClauseTitle) && empty($compClauseStory))
			{
				$compClause=$compClauseDate1;
			}

			// [only to_date]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && empty($compClauseStory))
			{
				$compClause=$compClauseDate2;
			}

			// [from_date & to_date]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && empty($compClauseStory))
			{
			$compClause=new AndWhereClause($compClauseDate1, $compClauseDate2);
			}

			// [only title]
			if (empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				$compClause=$compClauseTitle;
			}

			// [from_date & title]
			if (!empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClauseDate1, $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate1, $compClauseTitle);
					break;
				}
			}

			// [to_date & title]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClauseDate2, $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate2, $compClauseTitle);
					break;
				}
			}

			// [from_date, to_date & title]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && empty($compClauseStory))
			{
				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseTitle);
					break;
				}
			}

			// [only story]
			if (empty($compClauseDate1) && empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=$compClauseStory;
			}

			// [from_date & story]
			if (!empty($compClauseDate1) && empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClauseDate1, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate1, $compClauseStory);
					break;
				}
			}

			// [to_date & story]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND": $compClause=new AndWhereClause($compClauseDate2, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseDate2, $compClauseStory);
					break;
				}
			}

			// [from_date, to_date & story]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause(new AndWhereClause($compClauseDate1, $compClauseDate2), $compClauseStory);
					break;
				}
			}

			// [title & story]
			if (empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClauseTitle, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClauseTitle, $compClauseStory);
					break;
				}
			}

			// [from_date, title & story specified
			if (!empty($compClauseDate1) && empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=$compClauseDate1;

				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseTitle);
					break;

					case "OR":
					$compClause=new OrWhereClause($compClause, $compClauseTitle);
					break;
				}

				switch ($_REQUEST[operator_2])
				{
				case "AND":$compClause=new AndWhereClause($compClause, $compClauseStory);
				break;

				case "OR":
					$compClause=new OrWhereClause($compClause, $compClauseStory);
					break;
				}
			}

			// [to_date, title & story]
			if (empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=$compClauseDate2;

				switch ($_REQUEST[operator_1])
				{
				case "AND": $compClause=new AndWhereClause($compClause, $compClauseTitle);
				break;

				case "OR":$compClause=new OrWhereClause($compClause, $compClauseTitle);
				break;
				}

				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClause, $compClauseStory);
					break;
				}
			}

			// [from_date, to_date, title & story]
			if (!empty($compClauseDate1) && !empty($compClauseDate2) && !empty($compClauseTitle) && !empty($compClauseStory))
			{
				$compClause=new AndWhereClause($compClauseDate1, $compClauseDate2);

				switch ($_REQUEST[operator_1])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseTitle);
					break;

					case "OR":$compClause=new OrWhereClause($compClause, $compClauseTitle);
					break;
				}

				switch ($_REQUEST[operator_2])
				{
					case "AND":$compClause=new AndWhereClause($compClause, $compClauseStory);
					break;

					case "OR":$compClause=new OrWhereClause($compClause, $compClauseStory);
					break;
				}
			}
		}

		if ($realblogaction == "search")
		{
//			$compClause=serialize($compClause);
			session_write_close();

			if (isset($compClause))
			{
				$compClause=new AndWhereClause($compArchiveClause, $compClause);
			}
			else
			{
				unset ($realblogaction);
			}

			$records=$db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));

			$db_search_records=count($records);

			$t.= '<p>' . $plugin_tx['realblog']['search_searched_for'] . ' <b>"' . $_REQUEST['realblog_story'] . '"</b></p>';

			$t.='<p>' . $plugin_tx['realblog']['search_result'] . '<b> ' . $db_search_records . '</b></p>';
			$t.='<p><a href="' . preg_replace('/\&.*\z/', '', $_SERVER['REQUEST_URI']) . '"><b>' . $plugin_tx['realblog']['back_to_archive'] . '</b></a></p>';
		}
		else
		{
			// Select all realblog items from the DB
			// if (isset($compClause)) { $compClause=unserialize($compClause); }

			if (empty($compClause)) { $compClause=$compArchiveClause; }

			$records=$db->selectWhere($db_name, $compClause, -1,array(
			new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON),
			new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));
		}

		switch (strtolower($layout))
		{
			case "archive":
			$realblog_topics_total=count($records);

			$filter_total=0;

			if ($realblogaction != "search")
			{
				$currentYear=date("Y", time());

				if (!isset($realblogYear) || $realblogYear <= 0 || $realblogYear >= $currentYear || empty($realblogYear))
				{
					$realblogYear    =$currentYear;
					$currentMonth=date("n", time());
				}
				else
				{
					$currentMonth=12;
				}

				$_SESSION['realblogYear'] = $realblogYear;

				$next=($realblogYear < $currentYear) ? ($realblogYear + 1) : $currentYear;
				$back=$realblogYear - 1;
				$t="\n<div>&nbsp;</div>\n";
				$t.="\n<div class=\"realblog_table_paging\">\n<a href=\"" . $sn . "?" . $u[$s] . "&amp;realblogYear=" . $back
 . "\" title=\"" . $plugin_tx[$plugin]['tooltip_previousyear'] . "\">" . tag("img src=\"" . $plugin_images_folder . "btn_previous.gif\" alt=\"previous_img\"") . "</a>&nbsp;&nbsp;";
				$t.="<b>" . $plugin_tx[$plugin]['archive_year'] . $realblogYear . "</b>";
				$t.="&nbsp;&nbsp;<a href=\"" . $sn . "?" . $u[$s] . "&amp;realblogYear=" . $next . "\" title=\"" . $plugin_tx[$plugin]['tooltip_nextyear'] . "\">" .
tag("img src=\"" . $plugin_images_folder . "btn_next.gif\" alt=\"next_img\"") . "</a>";
				$t.="</div>";
				$t.="\n<div>&nbsp;</div>\n";
				$startmonth=mktime(NULL, NULL, NULL, 1, 1, $realblogYear);
				$endmonth=mktime(NULL, NULL, NULL, 12, 1, $realblogYear);
				$compClause=new AndWhereClause(new AndWhereClause(new SimpleWhereClause(REALBLOG_DATE, '>=', $startmonth, INTEGER_COMPARISON),new SimpleWhereClause(REALBLOG_DATE, '<=', $endmonth, INTEGER_COMPARISON)), new SimpleWhereClause(REALBLOG_STATUS, "=", 2, INTEGER_COMPARISON));
				$generalrealbloglist=$db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));



				for ($month=$currentMonth; $month >= 1; $month--)
				{
					$startmonth = mktime(NULL, NULL, NULL, $month, 1, $realblogYear);
					$endmonth   =mktime(NULL, NULL, NULL, $month + 1, 1, $realblogYear);
					$compClause =new AndWhereClause(new SimpleWhereClause(REALBLOG_STATUS, '=', 2, INTEGER_COMPARISON), new AndWhereClause(new SimpleWhereClause(REALBLOG_DATE, '>=', $startmonth, INTEGER_COMPARISON), new SimpleWhereClause(REALBLOG_DATE, '<', $endmonth, INTEGER_COMPARISON), new SimpleWhereClause(REALBLOG_STATUS, "=", 2,INTEGER_COMPARISON)));
					$realbloglist   =$db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));
					$monthString=strftime("%B %Y", mktime(NULL, NULL, NULL, $month, 1, $realblogYear));

					$month_search = explode(",", $plugin_tx['realblog']['date_month_search']);
					$month_replace = explode(",", $plugin_tx['realblog']['date_month_replace']);
					$monthString = str_ireplace($month_search,$month_replace,$monthString);

					if (count($realbloglist) > 0)
					{
						$t.="\n<h4>" . $monthString . "</h4>\n\n<ul class=\"realblog_archive\">\n";
						foreach ($realbloglist as $key => $field)
						{
							$t.= '<li>' . tag("img src=\"" . $plugin_images_folder . "realblog_item.gif" . "  \" alt=\"realblogitem_img\"") . "&nbsp;" . date($plugin_tx[$plugin]['date_format'], $field[REALBLOG_DATE]) . "&nbsp;&nbsp;&nbsp;<a href=\"" . $sn . "?" . $u[$s] . "&amp;" . str_replace(' ', '_', $field[REALBLOG_TITLE]) . "&amp;realblogaction=view&amp;realblogID=" . $field[REALBLOG_ID] . "&amp;page=" . $page . "\" title=\"" . $plugin_tx[$plugin]["tooltip_view"] . "\" >" . $field[REALBLOG_TITLE] . "</a></li>\n";
						}
						$t.= "</ul>\n";
					}
				}

				if (count($generalrealbloglist) == 0)
				{
					$t.=$plugin_tx[$plugin]['no_topics'];
				}
			}
			else
			{
				$currentMonth=12;
				$realbloglist    =$records;
				$t.="\n<div>&nbsp;</div>\n";

				if (count($realbloglist) > 0)
				{
					foreach ($realbloglist as $key => $field)
					{
						$month      = date("n", $field[REALBLOG_DATE]);
						$year       =date("Y", $field[REALBLOG_DATE]);
						$monthString=strftime("%B %Y", mktime(NULL, NULL, NULL, $month, 1, $year));

						if ($realblogmonth != $month)
						{
							$t.=($key != 0) ? tag('br') : "";
							$t.="<h4>" . $monthString . "</h4>\n";
							$realblogmonth=$month;
						}

						$t.='<p style="line-height: 1em;">'. tag('img src="' . $plugin_images_folder . 'realblog_item.gif" alt="calendar_img"').'&nbsp;' . date($plugin_tx[$plugin]['date_format'], $field[REALBLOG_DATE]) . "&nbsp;&nbsp;&nbsp;<a href=\"" . $sn . "?" . $u[$s] . "&amp;" . str_replace(' ', '_', $field[REALBLOG_TITLE]) . "&amp;realblogaction=view&amp;realblogID=" . $field[REALBLOG_ID] . "&amp;page=" . $page . "\" title=\"" . $plugin_tx[$plugin]["tooltip_view"] . "\" >" . $field[REALBLOG_TITLE] . "</a></p>\n\n";

					}
				}
				else
				{
					$t.=$plugin_tx[$plugin]['no_topics'];
				}
			}
			break;
		}
	}
	else
	{

		// Display the realblogitem for the given ID
		$record=$db->selectUnique($db_name, REALBLOG_ID, $realblogID);

		// Set the return page, based on the caling page
		if ($from_page == '' || empty($from_page))
		{
			$from_page=1;
		}

		$return_page=($from_page == $u[$s]) ? $u[$s] : $from_page;

		if (count($record) > 0)
		{

			// Show selected entry (archive above entry)
			$t ="\n<div class=\"realblog_show_box\">\n";

			// Redirect back to archive overview (archive, above entry) - GE 2010 - 11
			global $su, $sn, $s, $plugin_cf, $realblogYear;

			$t.= "\n" . '<div class="realblog_buttons"><span class="realblog_button">' . "\n" . '<a href="' . $sn . '?' . $su . '&amp;realblogYear=' . $_SESSION['realblogYear'] . '">' .$plugin_tx[$plugin]['archiv_back'] . '</a></span>';

			// "edit comments" button (realblog, above entry) - GE 2010 - 11
			if (function_exists('comments') && $plugin_cf['realblog']['comments_function'] == 'true' && $adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&comments&admin=plugin_main&action=plugin_text&selected=comments' . $realblogID . '.txt">' . $plugin_tx['realblog']['comment_edit'] . '</a></span>';
			}

			// "edit entry" button (realblog, above entry)
			if($adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&realblog&admin=plugin_main&action=modify_realblog&amp;realblogID=' . $realblogID . '">' . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
			}

			$t.='<div style="clear: both;"></div>';

			$t.= "\n".'</div>'."\n";

			$t.="<h4>" . $record[REALBLOG_TITLE] . "</h4>";
			$t.="\n<div class=\"realblog_show_date\">\n" . strftime($plugin_tx[$plugin]['display_date_format'], $record[REALBLOG_DATE]) . "\n</div>\n";
			$t.="\n<div class=\"realblog_show_story\">\n";

			if($record[REALBLOG_STORY] != '')
			{
				$t.= stripslashes(evaluate_scripting_crb($record[REALBLOG_STORY]));
			}
			else
			{
				$t.= stripslashes(evaluate_scripting_crb($record[REALBLOG_HEADLINE]));
			}

			$t.="\n</div>\n";
			$t.="</div>\n";
			$t.="\n<div>&nbsp;</div>\n";

			if (isset($printrealblog))
			{
				$tt="<html><head><title>" . sitename() . " - " . str_replace('_', ' ', $u[$s]) . "</title>";
				$tt.= tag('link rel="stylesheet" href="' . $pth['folder']['plugins'] . $plugin . '/css/stylesheet.css" type="text/css">');
				$tt.="</head><body>";
				$tt.=$t;
				$tt.="\n<div align=\"center\"\n><a href=javascript:void(0); onclick='window.close()'>" . $plugin_tx[$plugin][window_close] . "</a>&nbsp;&nbsp;&nbsp;<a href=javascript:void(0); onclick='window.print()'>" . $plugin_tx[$plugin][window_print] . "</a>\n</div>\n";
				$tt.='</body></html>';
				$t.="<script language='javascript' type='text/javascript'> var printinfo = '" . addslashes($tt) . "';
				win = window.open('', 'printversion', 'toolbar = 0, status = 0, width=600',scrollbars=1);
				win.document.write(printinfo);
				win.document.close();
				</script>";
			}

			// Redirect back to archive overview (archive, below entry) - GE 2010 - 11
			global $su, $sn, $s, $realblogYear, $page, $plugin_cf;

			$t.= "\n" . '<div class="realblog_buttons"><span class="realblog_button">' . "\n" . '<a href="' . $sn . '?' . $su . '&amp;realblogYear=' . $_SESSION['realblogYear'] . '">' .$plugin_tx[$plugin]['archiv_back'] . '</a></span>';

			// "edit comments" button (realblog, below entry) - GE 2010 - 11
			if (function_exists('comments') && $plugin_cf['realblog']['comments_function'] == 'true' && $adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&comments&admin=plugin_main&action=plugin_text&selected=comments' . $realblogID . '.txt">' . $plugin_tx['realblog']['comment_edit'] . '</a></span>';
			}

			// "edit entry" button (realblog, below entry)
			if($adm == 'true')
			{
				$t.='<span class="realblog_button"><a href="./?&realblog&admin=plugin_main&action=modify_realblog&amp;realblogID=' . $realblogID . '">' . $plugin_tx['realblog']['entry_edit'] . '</a></span>';
			}

			$t.='<div style="clear: both;"></div>';

			$t.= "\n".'</div>'."\n";

			// output comments in archive - GE 2010 - 11

			if (function_exists('comments') && $plugin_cf['realblog']['comments_function'] == 'true' && $record[REALBLOG_COMMENTS] == 'on')
			{
				$realblog_comments_id = 'comments'.$realblogID;
				if($plugin_cf['realblog']['comments_form_protected'] == 'true')
				{
					$t.= tag('br').comments($realblog_comments_id,'protected');
				}
				else
				{
					$t.= tag('br').comments($realblog_comments_id);
				}
			}
		}
	}
	$t.="<div class=\"realblog_credit\">\n
	Powered by <a href=\"http://www.ge-webdesign.de/cmsimplerealblog/?Demo_und_WebLog\">CMSimpleRealBlog\n
	</a>\n</div>\n";

	$c[$s]="";
	unset ($realblogaction);
	unset ($compClause);
	return ($t);
}

/*
********************************************************************************
* This function display's the realblog topics from within the template with a link to
* the realblogbox page and is used to be called from the CMSimple template.
* pre-conditions : a page including the script #cmsimple $output.=showrealblog();#
*                  must be present, otherwise the realblog item details won't be displayed.
* parameters :
*     - realblog_page [required] : this is the page containing the showrealblog() function
********************************************************************************
*/

function realbloglink ($options)
{
	global $pth, $sn, $plugin_tx, $plugin_cf, $u, $s, $c, $h, $sl, $page;

	$includeonfrontpage='false';
	$arguments=explode(",", $options);

	if (count($arguments > 0))
	{
		foreach ($arguments as $argument)
		{
			$property = explode("=", $argument);

			switch (strtolower($property[0]))
			{
				case "realblogpage":$realblog_page=$property[1];
				break;
			}
		}
	}

	// Check if the specified realblog_page realy exists
	$page_exists=false;

	foreach ($u as $key => $value)
	{
		if ($realblog_page === $value)
		{
			if ($s == $key)
			{
				$page_exists=true;
				break;
			}

			if (preg_match("/showrealblog\(.*/is", $c[$key]))
			{
				$page_exists=true;
				break;
			}
		}
	}

	if (!$page_exists)
	{
		return ("");
	}

	if (!empty($realblog_page) || $realblog_page != '')
	{
// print_r($_SESSION);

		// Register the current page in a session variable
		$plugin=basename(dirname(__FILE__), "/");

		// set general variables for the plugin
		$plugin_images_folder=$pth['folder']['plugins'] . $plugin . "/images/";
		$plugin_include_folder=$pth['folder']['plugins'] . $plugin . "/include/";
		setlocale(LC_ALL, $plugin_tx[$plugin]['date_locale']);

		$db_path = $pth['folder']['content'] . 'realblog/';
		$db_name="realblog.txt";

		// include the flatfile database class
		require_once ($plugin_include_folder . "flatfile.php");

		// declare the realblog database fields
		require_once ($plugin_include_folder . "fields.php");

		// connect to the realblog database file
		$db=new Flatfile();
		$db->datadir=$db_path;

		if (@$id == -1 || empty($id) || !isset($id))
		{
			if ($plugin_cf['realblog']['links_visible'] > 0)
			{
				$t ='<p class="realbloglink">
' . $plugin_tx['realblog']['links_visible_text'] . '
</p>
';
				// Select all published realblog items ordered by DATE descending within the publishing range
				$d=date("d", time());
				$m=date("n", time());
				$y=date("Y", time());
				$today=mktime(NULL, NULL, NULL, $m, $d, $y);
				$compClause=NULL;
				$compClause=new AndWhereClause(new SimpleWhereClause(REALBLOG_STATUS, "=", 1, INTEGER_COMPARISON));

				if (strtolower($includeonfrontpage) === 'true')
				{
					$compClause=new OrWhereClause(new SimpleWhereClause(REALBLOG_FRONTPAGE, "=", 'on', STRING_COMPARISON), $compClause);
				}

				$realbloglist=$db->selectWhere($db_name, $compClause, -1, array(new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));

				// Show the results
				$max_visible =$plugin_cf[$plugin]['links_visible'];
				$realblog_counter=0;

				if (count($realbloglist) > 0)
				{
					if ($max_visible <= 0 || empty($max_visible))
					{
						$max_visible=count($realbloglist);
					}
					$t.="\n<div class=\"realblog_tpl_show_box\">\n";
					foreach ($realbloglist as $index => $record)
					{
						$realblog_counter++;
						$t.="\n<div class=\"realblog_tpl_show_date\">\n".strftime($plugin_tx[$plugin]['display_date_format'], $record[REALBLOG_DATE]) . "\n</div>";
						$t.="\n<div class=\"realblog_tpl_show_title\">\n<a href=\"" . $sn . "?" . $realblog_page . "&amp;realblogaction=view&amp;realblogID=" . $record[REALBLOG_ID] . "&amp;page=1" . "\">" . $record[REALBLOG_TITLE] .'</a>'."\n".'</div>'."\n";

						// Limit the number of visible realblog items (set in the configuration; empty=all realblog)
						if ($plugin_cf[$plugin]['links_visible'] > 0)
						{
							if ($realblog_counter == $max_visible)
							{
								break;
							}
						}
					}
				$t.="\n".'<div style="clear: both;"></div></div>'."\n";
				}
				else
				{
					$t.=''.$plugin_tx[$plugin]['no_topics'].'';
				}
//				$t.='</div>'."\n";
			}
		}
	}
	else
	{
		$t.='';
	}
	return ($t);
}

/*
********************************************************************************
* This internal function makes a timestamp date from a give date
* parameters :
* - tmpdate [optional] : date to be converted to a timestamp (if omitted, the current date is used)
* The function also generates some internal settings, used in the datepicker.js function.
********************************************************************************
*/

function make_timestamp_dates1 ($tmpdate = null)
{
	global $plugin_cf, $plugin_tx, $date_separator1;

	// get plugin name
	$plugin        =basename(dirname(__FILE__), "/");
	$my_date_format=explode('/', $plugin_tx[$plugin]['date_format']);

	if (count($my_date_format) > 1)
	{
		$date_separator="/";
	}
    else
	{
		$my_date_format=explode('.', $plugin_tx[$plugin]['date_format']);

		if (count($my_date_format) > 1)
		{
			$date_separator=".";
		}
		else
		{
		$my_date_format=explode('-', $plugin_tx[$plugin]['date_format']);

		if (count($my_date_format) > 1)
			{
				$date_separator="-";
			}
		}
	}

	for ($aCounter=0; $aCounter <= 2; $aCounter++)
	{
		switch ($my_date_format[$aCounter])
		{
			case 'd':$dayposition=$aCounter;

			$my_detected_date_format[$dayposition]=$my_date_format[$aCounter];
			$cal_date_format[$dayposition]        ="DD";
			$regex[$dayposition]                 ='([0-9]{1,2})';
			break;

			case 'm':$monthposition=$aCounter;

			$my_detected_date_format[$monthposition]=$my_date_format[$aCounter];
			$cal_date_format[$monthposition]        ="MM";
			$regex[$monthposition]                  ='([0-9]{1,2})';
			break;

			case 'y':
			$yearposition                          =$aCounter;

			$my_detected_date_format[$yearposition]=$my_date_format[$aCounter];
			$cal_date_format[$yearposition]        ="YY";
			$regex[$yearposition]                  ='([0-9]{2})';
			break;

			case 'Y':
			$yearposition                          =$aCounter;

			$my_detected_date_format[$yearposition]=$my_date_format[$aCounter];
			$cal_date_format[$yearposition]        ="YYYY";
			$regex[$yearposition]                  ='([0-9]{4})';
			break;
		}
	}

	foreach ($my_detected_date_format as $key => $value)
	{
		if ($key < (count($my_detected_date_format) - 1)) { $date_format.=$value . $date_separator; }
		else
		{
		$date_format.=$value;
		}
	}

	foreach ($cal_date_format as $key => $value)
	{
		$cal_format.=$value;
	}

	foreach ($regex as $key => $value)
	{
		if ($key < (count($regex) - 1))
		{
			$regex_format.=$value . $date_separator;
		}
		else
		{
		$regex_format.=$value;
		}
	}

	if ($tmpdate == null)
	{
		$tmpdate=date($plugin_tx[$plugin]['date_format'], time());
	}

	if (ereg($regex_format, $tmpdate))
	{
		if($date_separator1=".")
		{
			$dateArr=explode('.', $tmpdate);
		}
		if($date_separator1="/")
		{
			$dateArr=explode('/', $tmpdate);
		}
		if($date_separator1="-")
		{
			$dateArr=explode('-', $tmpdate);
		}
		$m      =$dateArr[$monthposition];
		$d      =$dateArr[$dayposition];
		$y      =$dateArr[$yearposition];
	}

	$tmpdate=mktime(null, null, null, $m, $d, $y);
	return ($tmpdate);
}

/*
********************************************************************************
* This function makes a rss compatible newsfeed file
* parameters : none
********************************************************************************
*/

function realblog_export_rssfeed()
{
	global $tx, $pth, $sn, $u, $s, $c, $sl, $plugin_cf, $plugin_tx, $page;
	$plugin=basename(dirname(__FILE__), "/");
	// set general variables for the plugin
	include_once ($pth['folder']['plugins'] . $plugin . "/config/config.php");
	include_once ($pth['folder']['plugins'] . $plugin . "/languages/" . $sl . ".php");

	if (strtolower($plugin_tx[$plugin]['rss_enable']) == 'true')
	{
		$db_path = $pth['folder']['content'] . 'realblog/';
		$db_name="realblog.txt";

		$rss_path='./';
		$plugin_images_folder=$pth['folder']['plugins'] . $plugin . "/images/";
		$plugin_include_folder=$pth['folder']['plugins'] . $plugin . "/include/";


		//setlocale(LC_ALL,$plugin_tx[$plugin]['date_locale']);

		// declare the realblog database fields
		require_once ($plugin_include_folder . "fields.php");

		// include the flatfile database class
		require_once ($plugin_include_folder . "flatfile.php");

		// connect to the realblog database file
		$db=new Flatfile();
		$db->datadir=$db_path;

		if ($fp=@fopen($rss_path . "realblog_rss_feed.xml", "w+"))
		{
			fputs($fp, "<?xml version=\"1.0\" encoding=\"" . strtolower($plugin_cf[$plugin]['rss_encoding']) . "\"?>\n");
			fputs($fp,"<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:sy=\"http://purl.org/rss/1.0/modules/syndication/\" xmlns:admin=\"http://webns.net/mvcb/\" xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n");
			fputs($fp, "<channel>\n");
			fputs($fp, "<title>" . $plugin_tx[$plugin]['rss_title'] . "</title>\n");
			fputs($fp, "<link>" . $plugin_tx['realblog']['rss_page'] . "</link>\n");
			fputs($fp, "<description>" . $plugin_tx[$plugin]['rss_description'] . "</description>\n");
			fputs($fp, "<language>" . $plugin_tx[$plugin]['rss_language'] . "</language>\n");
			fputs($fp, "<copyright>" . $plugin_cf[$plugin]['rss_copyright'] . "</copyright>\n");
			fputs($fp, "<managingEditor>" . $plugin_cf[$plugin]['rss_editor'] . "</managingEditor>\n");
			fputs($fp, "<image>\n");
			fputs($fp, "<title>" . $plugin_tx[$plugin]['rss_title'] . "</title>\n");
			fputs($fp, "<url>" . $plugin_cf[$plugin]['rss_logo'] . "</url>\n");
			fputs($fp, "<link>" . $plugin_tx['realblog']['rss_page'] . "</link>\n");
			fputs($fp, "<width>65</width>\n");
			fputs($fp, "<height>35</height>\n");
			fputs($fp, "<description>" . $plugin_tx[$plugin]['rss_description'] . "</description>\n");
			fputs($fp, "</image>\n");
			$compClause=new SimpleWhereClause(REALBLOG_RSSFEED, "=", "on", STRING_COMPARISON);
            $realbloglist  =$db->selectWhere($db_name, $compClause, -1,array(new OrderBy(REALBLOG_DATE, DESCENDING, INTEGER_COMPARISON), new OrderBy(REALBLOG_ID, DESCENDING, INTEGER_COMPARISON)));

			// Show the RSS realblog items
			if (count($realbloglist) > 0)
			{
				foreach ($realbloglist as $index => $record)
				{
					fputs($fp, "<item>\n");
					$title="<title>" . htmlspecialchars(stripslashes($record[REALBLOG_TITLE])) . "</title>\n";
					$link="<link>" . $plugin_tx[$plugin]["rss_page"] . "&amp;realblogaction=view" . "&amp;realblogID=" . $record[REALBLOG_ID] . "&amp;page=" . $page . "</link>\n";
					$description="<description>" . preg_replace('/({{{PLUGIN:.*?}}}|{{{function:.*?}}}|#CMSimple .*?#)/is', '',htmlspecialchars(stripslashes($record[REALBLOG_HEADLINE]))) . "</description>\n";
					$pubDate="<pubDate>" . date("r", $record[REALBLOG_DATE]) . "</pubDate>\n";
					fputs($fp, $title);
					fputs($fp, $link);
					fputs($fp, $description);
					fputs($fp, $pubDate);
					fputs($fp, "</item>\n");
				}
			}

			fputs($fp, "</channel>\n");
			fputs($fp, "</rss>\n");
			fclose ($fp);
		}
	}
}

/*
********************************************************************************
* This function displays a graphical hyperlink to the newsfeed file
* parameters :
* - replace [optional]    : true/false or 1/0
*                           when set to true, the scripting line will be
*                           replaced with the content generated by the function
********************************************************************************
*/

function realblog_rss_adv ($options = NULL)
{
	global $plugin_tx, $plugin_cf, $cf, $pth, $sl, $c, $s, $page;

	$db_path = $pth['folder']['content'] . 'realblog/';

	$plugin              =basename(dirname(__FILE__), "/");
	$plugin_images_folder=$pth['folder']['plugins'] . $plugin . "/images/";

	$rss_path = './';

	$option_replace      ='false';

	// determine the function arguments (separated by a comma)
	$arguments           =explode(",", $options);

	if (count($arguments > 0))
	{
		foreach ($arguments as $argument)
		{
			$property = explode("=", $argument);

			switch ($property[0])
				{
                case "replace":
				if (strtolower($property[1]) == "true" || strtolower($property[1]) == "false" || strtolower($property[1]) == 1 || strtolower($property[1]) == 0)
				{
					$option_replace=strtolower($property[1]);
				}
				break;
			}
		}
	}
    else
	{
		$option_replace='false';
	}

	$tt='';
	$tt= tag('br') . '<a href="' . $rss_path . 'realblog_rss_feed.xml">' . tag('img src="' . $plugin_images_folder . 'rss.png" alt="' . $plugin_tx[$plugin]['rss_tooltip'] . '" style="border: 0;"') . '</a>';

	if ($option_replace === is_bool('true'))
	{
		$tt=preg_replace("/" .$cf['scripting']['regexp']. "/is", $tt, $c[$s]);
	}
	$tt.="<div class=\"realblog_credit\">\n
	Powered by <a href=\"http://www.ge-webdesign.de/cmsimplerealblog/?Demo_und_WebLog\">CMSimpleRealBlog\n
	</a>\n</div>\n";

	return ($tt);
}
?>