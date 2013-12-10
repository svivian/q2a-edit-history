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

	function init_queries( $tableslc )
	{
		$tablename = qa_db_add_table_prefix($this->pluginkey);

		if ( !in_array($tablename, $tableslc) )
		{
			return
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
		}

		return null;
	}

	function admin_form( &$qa_content )
	{
		$saved_msg = '';
		$error = null;

		if ( qa_clicked('edit_history_save') )
		{
			if ( qa_post_text('eh_active') )
			{
				$sql = 'SHOW TABLES LIKE "^'.$this->pluginkey.'"';
				$result = qa_db_query_sub($sql);
				$rows = qa_db_read_all_assoc($result);
				if ( count($rows) > 0 )
					qa_opt( $this->opt_active, '1' );
				else
				{
					$error = array(
						'type' => 'custom',
						'error' => qa_lang_html('edithistory/admin_notable') . '<a href="' . qa_path('install') . '">' . qa_lang_html('edithistory/admin_create_table') . '</a>',
					);
				}

				$saved_msg = qa_lang_html('admin/options_saved');
			}
			else
				qa_opt( $this->opt_active, '0' );

			qa_opt($this->opt_perms, qa_post_text('ua_user_perms'));
		}

		// plugin state
		$eh_active = qa_opt($this->opt_active);
		$active_field = array(
			'type' => 'checkbox',
			'label' => qa_lang_html('edithistory/admin_active'),
			'tags' => 'NAME="eh_active"',
			'value' => $eh_active === '1',
			'note' => qa_lang_html('edithistory/admin_active_note'),
		);

		// get list of user permissions
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		$permitoptions = qa_admin_permit_options(QA_PERMIT_ALL, QA_PERMIT_SUPERS, false, false);
		$view_perms = qa_opt($this->opt_perms);
		$selected = isset( $permitoptions[$view_perms] ) ? $permitoptions[$view_perms] : QA_PERMIT_ALL;

		$perms_field = array(
			'type' => 'select',
			'label' => qa_lang_html('edithistory/admin_perms'),
			'tags' => 'NAME="ua_user_perms"',
			'options' => $permitoptions,
			'value' => $selected,
			'note' => qa_lang_html('edithistory/admin_perms_note'),
		);

		$form = array(
			'ok' => $saved_msg,
			'style' => 'wide',

			'fields' => array(
				$active_field,
				$perms_field,
			),

			'buttons' => array(
				array(
					'label' => qa_lang_html('admin/save_options_button'),
					'tags' => 'name="edit_history_save"',
				),
			),

		);

		if ( $error !== null )
			$form['fields'][] = $error;

		return $form;
	}


	function process_event( $event, $userid, $handle, $cookieid, $params )
	{
		// only interested in edits
		$attachevents = array('q_edit', 'a_edit');
		if ( !in_array( $event, $attachevents ) )
			return;

		// question title or content was not changed
		if ( $event == 'q_edit' && $params['title'] === $params['oldtitle'] && $params['content'] === $params['oldcontent'] )
			return;

		// answer content was not changed
		if ( $event == 'a_edit' && $params['content'] === $params['oldcontent'] )
			return;

		// check if tracking is active
		if ( !qa_opt($this->opt_active) )
			return;

		// don't log 'ninja' edits (within 5 minutes)
		$now = time();
		$oldkey = $event == 'q_edit' ? 'oldquestion' : 'oldanswer';
		$lastupdate = $params[$oldkey]['updated'];
		// new posts have a NULL updated time
		if ( $lastupdate == null )
			$lastupdate = $params[$oldkey]['created'];
		if ( abs($now-$lastupdate) < 300 )
			return;

		$userid = qa_get_logged_in_userid();
		$sql =
			'INSERT INTO ^edit_history (postid, updated, title, content, tags, userid, reason) ' .
			'VALUES (#, NOW(), $, $, $, #, $)';

		return qa_db_query_sub( $sql, $params['postid'], @$params['oldtitle'], $params['oldcontent'], @$params['oldtags'], $userid, '' );
	}

}
