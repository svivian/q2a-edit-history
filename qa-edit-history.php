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
	private $opt_perms = 'edit_history_view_perms';
	private $opt_ninja = 'edit_history_ninja';

	public function option_default($option)
	{
		switch ($option) {
			case 'edit_history_active':
				return 0;
			case 'edit_history_view_perms':
				return QA_PERMIT_USERS;
			case 'edit_history_ninja':
				return 300;
		}

		return null;
	}

	public function init_queries($tableslc)
	{
		$tablename = qa_db_add_table_prefix($this->pluginkey);

		if (!in_array($tablename, $tableslc)) {
			$sql =
				'CREATE TABLE IF NOT EXISTS ^'.$this->pluginkey.' ( ' .
				'`postid` int(10) unsigned NOT NULL, ' .
				'`updated` datetime NOT NULL, ' .
				'`title` varchar(800) DEFAULT NULL, ' .
				'`content` varchar(8000) DEFAULT NULL, ' .
				'`tags` varchar(800) DEFAULT NULL, ' .
				'`userid` int(10) unsigned DEFAULT NULL, ' .
				'`reason` varchar(800) DEFAULT NULL, ' .
				'PRIMARY KEY (`postid`,`updated`) ' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8;';

			return array($sql);
		}

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

			qa_opt($this->opt_perms, qa_post_text('eh_user_perms'));
			qa_opt($this->opt_ninja, qa_post_text('eh_ninja_time'));

			$saved_msg = qa_lang_html('admin/options_saved');
		}

		// plugin state
		$active_field = array(
			'label' => qa_lang_html('edithistory/admin_active'),
			'note' => qa_lang_html('edithistory/admin_active_note'),
			'type' => 'checkbox',
			'tags' => 'name="eh_active"',
			'value' => qa_opt($this->opt_active) === '1',
		);

		// get list of user permissions
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		$permitoptions = qa_admin_permit_options(QA_PERMIT_ALL, QA_PERMIT_SUPERS, false, false);
		$view_perms = qa_opt($this->opt_perms);
		$selected = isset($permitoptions[$view_perms]) ? $permitoptions[$view_perms] : QA_PERMIT_ALL;

		$perms_field = array(
			'label' => qa_lang_html('edithistory/admin_perms'),
			'note' => qa_lang_html('edithistory/admin_perms_note'),
			'type' => 'select',
			'tags' => 'name="eh_user_perms"',
			'options' => $permitoptions,
			'value' => $selected,
		);

		// ninja edit time option
		$ninja_field = array(
			'label' => qa_lang_html('edithistory/admin_ninja'),
			'note' => qa_lang_html('edithistory/admin_ninja_note'),
			'type' => 'number',
			'tags' => 'name="eh_ninja_time"',
			'value' => qa_opt($this->opt_ninja),
		);

		$form = array(
			'ok' => $saved_msg,
			'style' => 'wide',

			'fields' => array(
				$active_field,
				$perms_field,
				$ninja_field,
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
		if (!in_array( $event, $attachevents ))
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
		if (abs($now-$lastupdate) < qa_opt($this->opt_ninja))
			return;

		return $this->db_insert_edit($params);
	}

	// add the old content to the edit_history table
	private function db_insert_edit(&$params)
	{
		$userid = qa_get_logged_in_userid();
		$sql =
			'INSERT INTO ^edit_history (postid, updated, title, content, tags, userid)
			 VALUES (#, NOW(), $, $, $, #)';

		return qa_db_query_sub($sql, $params['postid'], @$params['oldtitle'], @$params['oldcontent'], @$params['oldtags'], $userid);
	}

}
