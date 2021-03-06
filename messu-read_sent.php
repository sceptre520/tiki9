<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'user_messages';
require_once ('tiki-setup.php');
include_once ('lib/messu/messulib.php');
$access->check_user($user);
$access->check_feature('feature_messages');
$access->check_permission('tiki_p_messages');
if (isset($_REQUEST["delete"])) {
	check_ticket('messu-read_sent');
	$messulib->delete_message($user, $_REQUEST['msgdel'], 'sent');
}
$smarty->assign('sort_mode', $_REQUEST['sort_mode']);
$smarty->assign('find', $_REQUEST['find']);
$smarty->assign('flag', $_REQUEST['flag']);
$smarty->assign('offset', $_REQUEST['offset']);
$smarty->assign('flagval', $_REQUEST['flagval']);
$smarty->assign('priority', $_REQUEST['priority']);
$smarty->assign('legend', '');
if (!isset($_REQUEST['msgId']) || $_REQUEST['msgId'] == 0) {
	$smarty->assign('legend', tra("No more messages"));
	$smarty->assign('mid', 'messu-read_sent.tpl');
	$smarty->display("tiki.tpl");
	die;
}
if (isset($_REQUEST['action'])) {
	$messulib->flag_message($user, $_REQUEST['msgId'], $_REQUEST['action'], $_REQUEST['actionval'], 'sent');
}
// Using the sort_mode, flag, flagval and find get the next and prev messages
$smarty->assign('msgId', $_REQUEST['msgId']);
$next = $messulib->get_next_message($user, $_REQUEST['msgId'], $_REQUEST['sort_mode'], $_REQUEST['find'], $_REQUEST['flag'], $_REQUEST['flagval'], $_REQUEST['priority'], 'sent');
$prev = $messulib->get_prev_message($user, $_REQUEST['msgId'], $_REQUEST['sort_mode'], $_REQUEST['find'], $_REQUEST['flag'], $_REQUEST['flagval'], $_REQUEST['priority'], 'sent');
$smarty->assign('next', $next);
$smarty->assign('prev', $prev);
// Get the message and assign its data to template vars
$msg = $messulib->get_message($user, $_REQUEST['msgId'], 'sent');
$smarty->assign('msg', $msg);
ask_ticket('messu-read_sent');
include_once ('tiki-section_options.php');
include_once ('tiki-mytiki_shared.php');
$smarty->assign('mid', 'messu-read_sent.tpl');
$smarty->display("tiki.tpl");
