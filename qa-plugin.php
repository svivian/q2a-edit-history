<?php
/*
	Plugin Name: Edit History
	Plugin URI: https://github.com/ElephantsGroup/q2a-edit-history
	Plugin Description: Edit History plugin for Q2A
	Plugin Version: 1.1.1
	Plugin Date: 2014-02-12
	Plugin Author: Scott Vivian
	Plugin Author URI: http://codelair.co.uk/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.4
	Plugin Update Check URI: https://raw.github.com/ElephantsGroup/q2a-edit-history/master/qa-plugin.php

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}


qa_register_plugin_module('event', 'qa-edit-history.php', 'qa_edit_history', 'Edit History');
qa_register_plugin_module('page', 'qa-edh-revisions.php', 'qa_edh_revisions', 'Post revisions');
qa_register_plugin_layer('qa-edh-layer.php', 'Edit History Layer');
qa_register_plugin_phrases('qa-edh-lang-*.php', 'edithistory');



// checks if the current user is allowed to view edit history
function qa_edit_history_perms()
{
	$permit = qa_opt('edit_history_view_perms');
	$userid = qa_get_logged_in_userid();
	$userlevel = qa_get_logged_in_level();
	$userflags = qa_get_logged_in_flags();

	return qa_permit_value_error($permit, $userid, $userlevel, $userflags);
}
