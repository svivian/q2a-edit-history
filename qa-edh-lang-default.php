<?php
/*
	Question2Answer Edit History plugin
	License: http://www.gnu.org/licenses/gpl.html
*/

return array(
	'plugin_title' => 'Edit History',

	'main_title' => 'Recent post edits',
	'revision_title' => 'Revisions for post #^',

	'no_revisions' => 'No revisions for this post',
	'back_to_post' => 'Return to post',
	'edited_when_by' => 'Edited ^1 by ^2',
	'original_post_by' => 'Posted ^1 by ^2',
	'content_unchanged' => '(Content unchanged)',

	'current_revision' => 'Current revision',
	'revert' => 'Revert',
	'revert_warning' => 'Are you sure you want to roll back to this revision?',
	'delete' => 'Delete',
	'delete_warning' => 'Are you sure you want to DELETE this revision? There is no undo.',

	'need_login' => 'Please ^1log in^2 or ^3register^4 to view this page.',
	'no_user_perms' => 'Sorry, you do not have permission to view this page.',

	'admin_notable' => 'Database table is not set up yet.',
	'admin_create_table' => 'Create table',

	'admin_active' => 'Edit History active',
	'admin_active_note' => 'Untick to stop tracking post edits.',
	'admin_ninja' => 'Ninja edit time',
	'admin_ninja_note' => 'Time (in seconds) between logged edits.',

	'admin_perms' => 'Edit History visible for',
	'admin_perms_note' => 'User level allowed to see post revisions.',
	'admin_revert' => 'Reverting/Deleting available for',
	'admin_revert_note' => 'User level allowed to roll back and delete revisions.',
);
