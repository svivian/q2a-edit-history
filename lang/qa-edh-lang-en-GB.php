<?php
/*
	Question2Answer Edit History plugin
	License: http://www.gnu.org/licenses/gpl.html
*/

return array(
	'request_title' => 'Edit History',
	'main_title' => 'Recent post edits',
	'revision_title' => 'Edit history for post #^',
	'page_description' => '<p>This page will list posts that have been edited recently.</p>',

	'no_revisions' => 'No revisions for this post',
	'back_to_post' => 'Back to post',
	'edited_when_by' => 'Edited ^1 by ^2',
	'original_post_by' => 'Posted ^1 by ^2',

	'admin_title' => 'Post Revisions',
	'admin_notable' => 'Database table is not set up yet.',
	'admin_create_table' => 'Create table',

	'admin_active' => 'Edit History active',
	'admin_active_note' => 'Untick to stop tracking post edits.',
	'admin_perms' => 'Edit History visible for',
	'admin_perms_note' => 'User level allowed to see post revisions.',

	'ninja_edit_time' => 'Time between two accepted edit',
	'seconds' => 'seconds',
	'ninja_edit_time_note' => 'If two consecutive edits occurs in this period of time, the second one overrides first. (input 0 to disable this option)',
	'view_permission' => 'Minimum level to view',
	'view_permission_note' => 'Which level of users can see edit history records.',
	'permission_error' => 'Sorry, you do not have permissions to view edit history records.',
	'enabled_external_users' => 'Enable external users',
	'external_users_table' => 'External users table name',
	'external_users_table_key' => 'External users table key name',
	'external_users_table_handle' => 'External users table handle field name',
	'incorrect_entry' => 'Incorrect Entries',
	'edit_locked' => 'This post has edited already. Please edit after ^ seconds.',
);
