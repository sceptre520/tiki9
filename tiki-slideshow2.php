<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'wiki page';
require_once ('tiki-setup.php');
include_once ('lib/structures/structlib.php');
include_once ('lib/wiki/wikilib.php');

$access->check_feature('feature_wiki');
$page_ref_id = $_REQUEST['page_ref_id'];
if (!isset($page_ref_id)) {
	$smarty->assign('msg', tra("Page must be defined inside a structure to use this feature"));
	$smarty->display("error.tpl");
	die;
}
$smarty->assign('structure', 'y');
$navigation_info = $structlib->get_navigation_info($page_ref_id);
$page_info = $structlib->s_get_page_info($page_ref_id);
$smarty->assign('next_info', $navigation_info["next"]);
$smarty->assign('prev_info', $navigation_info["prev"]);
$smarty->assign('home_info', $navigation_info["home"]);
$smarty->assign('page_info', $page_info);
$page = $page_info["pageName"];
if (!($info = $tikilib->get_page_info($page))) {
	$smarty->assign('msg', tra('Page cannot be found'));
	$smarty->display('error.tpl');
	die;
}
// Now check permissions to access this page
$tikilib->get_perm_object($page, 'wiki page', $info);

$access->check_permission('tiki_p_view');
// BreadCrumbNavigation here
// Get the number of pages from the default or userPreferences
// Remember to reverse the array when posting the array
$anonpref = $prefs['userbreadCrumb'];
if (!isset($_SESSION["breadCrumb"])) {
	$_SESSION["breadCrumb"] = array();
}
if (!in_array($page, $_SESSION["breadCrumb"])) {
	if (count($_SESSION["breadCrumb"]) > $prefs['userbreadCrumb']) {
		array_shift($_SESSION["breadCrumb"]);
	}
	array_push($_SESSION["breadCrumb"], $page);
} else {
	// If the page is in the array move to the last position
	$pos = array_search($page, $_SESSION["breadCrumb"]);
	unset($_SESSION["breadCrumb"][$pos]);
	array_push($_SESSION["breadCrumb"], $page);
}
// Now increment page hits since we are visiting this page
if ($prefs['count_admin_pvs'] == 'y' || $user != 'admin') {
	$tikilib->add_hit($page);
}
// Get page data
$info = $tikilib->get_page_info($page);
$slide_data = $tikilib->parse_data($info["data"]);
$smarty->assign('slide_data', $slide_data);
// Verify lock status
if ($info["flag"] == 'L') {
	$smarty->assign('lock', true);
} else {
	$smarty->assign('lock', false);
}
// If not locked and last version is user version then can undo
$smarty->assign('canundo', 'n');
if ($info["flag"] != 'L' && (($tiki_p_edit == 'y' && $info["user"] == $user) || ($tiki_p_remove == 'y'))) {
	$smarty->assign('canundo', 'y');
}
if ($tiki_p_admin_wiki == 'y') {
	$smarty->assign('canundo', 'y');
}
$smarty->assign_by_ref('lastModif', $info["lastModif"]);
if (empty($info["user"])) {
	$info["user"] = 'anonymous';
}
$smarty->assign_by_ref('lastUser', $info["user"]);
include_once ('tiki-section_options.php');
$smarty->assign('wiki_extras', 'y');
ask_ticket('slideshow2');
// Display the Index Template
$smarty->assign('dblclickedit', 'y');
$smarty->assign('mid', 'tiki-show_page.tpl');
$smarty->display("tiki-slideshow.tpl");
