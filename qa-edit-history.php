<?php
/*
	Question2Answer Edit History plugin
	License: http://www.gnu.org/licenses/gpl.html
*/

require_once QA_INCLUDE_DIR.'qa-app-users.php';

class qa_edit_history
{
	private $pluginkey = 'edit_history';
	private $opt_active = 'edit_history_active';
	private $opt_ninja = 'edit_history_ninja';
	private $opt_perms = 'edit_history_view_perms';
	private $opt_admin = 'edit_history_admin_perms';

	public function option_default($option)
	{
		switch ($option) {
			case $this->opt_active:
				return 0;
			case $this->opt_ninja:
				return 300;
			case $this->opt_perms:
				return QA_PERMIT_USERS;
			case $this->opt_admin:
				return QA_PERMIT_ADMINS;
		}

		return null;
	}

	public function init_queries($tableslc)
	{
		$queries = array(
			// [0]: version 1
			'CREATE TABLE IF NOT EXISTS ^'.$this->pluginkey.' (
			   postid int(10) unsigned NOT NULL,
			   updated datetime NOT NULL,
			   title varchar(800) DEFAULT NULL,
			   content varchar(8000) DEFAULT NULL,
			   tags varchar(800) DEFAULT NULL,
			   userid int(10) unsigned DEFAULT NULL,
			   reason varchar(800) DEFAULT NULL,
			   PRIMARY KEY (postid, updated)
			 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

			// [1-3]: version 2
			'ALTER TABLE ^'.$this->pluginkey.' DROP PRIMARY KEY;',
			'ALTER TABLE ^'.$this->pluginkey.' ADD id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;',
			'ALTER TABLE ^'.$this->pluginkey.' ADD UNIQUE(postid, updated, userid);',
		);
		$tablename = qa_db_add_table_prefix($this->pluginkey);

		// no edit history table: run all queries
		if (!in_array($tablename, $tableslc))
			return $queries;

		// table exists: check if it's at current version
		$sqlCols = 'SHOW COLUMNS FROM ^'.$this->pluginkey;
		$fields = qa_db_read_all_values(qa_db_query_sub($sqlCols));
		if (!in_array('id', $fields))
			return array_slice($queries, 1);

		// all up to date
		return null;
	}

	public function admin_form(&$qa_content)
	{
		$saved_msg = '';
		$error = null;

		// save admin options
		if (qa_clicked('edit_history_save')) {
			$active = qa_post_text('eh_active') ? '1' : '0';
			qa_opt($this->opt_active, $active);

			qa_opt($this->opt_ninja, qa_post_text('eh_ninja_time'));
			qa_opt($this->opt_perms, qa_post_text('eh_user_perms'));
			qa_opt($this->opt_admin, qa_post_text('eh_user_admin'));

			$saved_msg = qa_lang_html('admin/options_saved');
		}

		// get list of user permissions
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		$permitopts_view = qa_admin_permit_options(QA_PERMIT_ALL, QA_PERMIT_SUPERS, false, false);
		$permitopts_admin = qa_admin_permit_options(QA_PERMIT_ALL, QA_PERMIT_SUPERS, false, false);
		// check if options are set
		$view_perms = qa_opt($this->opt_perms);
		$admin_perms = qa_opt($this->opt_admin);
		$sel_perms = isset($permitopts_view[$view_perms]) ? $permitopts_view[$view_perms] : $this->option_default($this->opt_perms);
		$sel_admin = isset($permitopts_admin[$admin_perms]) ? $permitopts_admin[$admin_perms] : $this->option_default($this->opt_admin);

		$form = array(
			'ok' => $saved_msg,
			'style' => 'wide',

			'fields' => array(
				// plugin state
				array(
					'label' => qa_lang_html('edithistory/admin_active'),
					'note' => qa_lang_html('edithistory/admin_active_note'),
					'type' => 'checkbox',
					'tags' => 'name="eh_active"',
					'value' => qa_opt($this->opt_active) === '1',
				),
				// ninja edit time
				array(
					'label' => qa_lang_html('edithistory/admin_ninja'),
					'note' => qa_lang_html('edithistory/admin_ninja_note'),
					'type' => 'number',
					'tags' => 'name="eh_ninja_time"',
					'value' => qa_opt($this->opt_ninja),
				),
				// viewing permissions
				array(
					'label' => qa_lang_html('edithistory/admin_perms'),
					'note' => qa_lang_html('edithistory/admin_perms_note'),
					'type' => 'select',
					'tags' => 'name="eh_user_perms"',
					'options' => $permitopts_view,
					'value' => $sel_perms,
				),
				// reverting/deleting permissions
				array(
					'label' => qa_lang_html('edithistory/admin_revert'),
					'note' => qa_lang_html('edithistory/admin_revert_note'),
					'type' => 'select',
					'tags' => 'name="eh_user_admin"',
					'options' => $permitopts_admin,
					'value' => $sel_admin,
				),
			),

			'buttons' => array(
				array(
					'label' => qa_lang_html('admin/save_options_button'),
					'tags' => 'name="edit_history_save"',
				),
			),

		);

		if ($error !== null)
			$form['fields'][] = $error;

		return $form;
	}


	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		// only interested in edits
		$attachevents = array('q_edit', 'a_edit');
		if (!in_array($event, $attachevents))
			return;

		// question title or content was not changed
		if ($event == 'q_edit' && $params['title'] === $params['oldtitle'] && $params['content'] === $params['oldcontent'])
			return;

		// answer content was not changed
		if ($event == 'a_edit' && $params['content'] === $params['oldcontent'])
			return;

		// check if tracking is active
		if (!qa_opt($this->opt_active))
			return;

		// don't log 'ninja' edits (within 5 minutes)
		$now = time();
		$oldkey = $event == 'q_edit' ? 'oldquestion' : 'oldanswer';
		$lastupdate = $params[$oldkey]['updated'];

		// new posts have a NULL updated time
		if ($lastupdate == null)
			$lastupdate = $params[$oldkey]['created'];

		// need last user to check ninja edits
		$lastuserid = $this->db_last_userid($params['postid']);
		if ($userid == $lastuserid && abs($now-$lastupdate) < qa_opt($this->opt_ninja))
			return;

		return $this->db_insert_edit($params);
	}

	// get user of the last revision
	private function db_last_userid($postid)
	{
		$sql = 'SELECT userid FROM ^'.$this->pluginkey.' WHERE postid=# ORDER BY updated DESC LIMIT 1';
		$result = qa_db_query_sub($sql, $postid);
		return qa_db_read_one_value($result, true);
	}

	// add the old content to the edit_history table
	private function db_insert_edit($params)
	{
		$userid = qa_get_logged_in_userid();
		$sql =
			'INSERT INTO ^'.$this->pluginkey.' (postid, updated, title, content, tags, userid)
			 VALUES (#, NOW(), $, $, $, #)';

		return qa_db_query_sub($sql, $params['postid'], @$params['oldtitle'], @$params['oldcontent'], @$params['oldtags'], $userid);
	}

}
