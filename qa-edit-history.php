<?php
/*
	Question2Answer Edit History plugin
	License: http://www.gnu.org/licenses/gpl.html
*/

require_once QA_INCLUDE_DIR.'qa-app-users.php';

class qa_edit_history
{
	private $pluginkey = 'edit_history';
	private $optactive = 'edit_history_active';
	private $ninja_edit_time = 'edit_history_NET';
	private $view_permission = 'edit_history_view_permission';
	private $enabled_external_users = 'edit_history_EEU';
	private $external_users_table = 'edit_history_EUT';
	private $external_users_table_key = 'edit_history_EUTK';
	private $external_users_table_handle = 'edit_history_EUTH';

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
		$permitoptions = qa_admin_permit_options(QA_PERMIT_ALL, QA_PERMIT_SUPERS, false, false);

		if ( qa_clicked('edit_history_save') )
		{
			if( !$this->validate_data() )
			{
				$saved_msg = qa_lang_html('edithistory/incorrect_entry');
			}
			else
			{
				if ( qa_post_text('eh_active') )
				{
					$sql = 'SHOW TABLES LIKE "^'.$this->pluginkey.'"';
					$result = qa_db_query_sub($sql);
					$rows = qa_db_read_all_assoc($result);
					if ( count($rows) > 0 )
					{
						qa_opt( $this->optactive, '1' );
					}
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
					qa_opt( $this->optactive, '0' );
				qa_opt( $this->ninja_edit_time, (int)qa_post_text('ninja_edit_time') );
				qa_opt( $this->view_permission, (int)qa_post_text('view_permission') );
				if ( qa_post_text('enabled_external_users') ) qa_opt( $this->enabled_external_users, '1' );
				else qa_opt( $this->enabled_external_users, '0' );
				qa_opt( $this->external_users_table, qa_post_text('external_users_table') );
				qa_opt( $this->external_users_table_key, qa_post_text('external_users_table_key') );
				qa_opt( $this->external_users_table_handle, qa_post_text('external_users_table_handle') );
			}
		}

		$eh_active = qa_opt($this->optactive);
		$ninja_edit_time = qa_opt($this->ninja_edit_time);
		$view_permission = qa_opt($this->view_permission);
		$enabled_external_users = qa_opt($this->enabled_external_users);
		$external_users_table = qa_opt($this->external_users_table);
		$external_users_table_key = qa_opt($this->external_users_table_key);
		$external_users_table_handle = qa_opt($this->external_users_table_handle);

		$form = array(
			'ok' => $saved_msg,

			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => qa_lang_html('edithistory/admin_active'),
					'tags' => 'NAME="eh_active"',
					'value' => $eh_active === '1',
					'note' => qa_lang_html('edithistory/admin_active_note'),
				),
				array(
					'type' => 'number',
					'label' => qa_lang_html('edithistory/ninja_edit_time'),
					'suffix' => qa_lang_html('edithistory/seconds'),
					'tags' => 'NAME="ninja_edit_time"',
					'value' => $ninja_edit_time,
					'note' => qa_lang_html('edithistory/ninja_edit_time_note'),
				),
				array(
					'type' => 'select',
					'label' => qa_lang_html('edithistory/view_permission'),
					'tags' => 'NAME="view_permission"',
					'value' =>  @$permitoptions[$view_permission],
					'options' => $permitoptions,
					'note' => qa_lang_html('edithistory/view_permission_note'),
				),
				array(
					'type' => 'checkbox',
					'label' => qa_lang_html('edithistory/enabled_external_users'),
					'tags' => 'NAME="enabled_external_users"',
					'value' => $enabled_external_users === '1',
				),
				array(
					'type' => 'text',
					'label' => qa_lang_html('edithistory/external_users_table'),
					'tags' => 'NAME="external_users_table"',
					'value' => $external_users_table,
				),
				array(
					'type' => 'text',
					'label' => qa_lang_html('edithistory/external_users_table_key'),
					'tags' => 'NAME="external_users_table_key"',
					'value' => $external_users_table_key,
				),
				array(
					'type' => 'text',
					'label' => qa_lang_html('edithistory/external_users_table_handle'),
					'tags' => 'NAME="external_users_table_handle"',
					'value' => $external_users_table_handle,
				),
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

	private function validate_data()
	{
		$ret = true;
		
		$table = $_POST['external_users_table'];
		$table_key = $_POST['external_users_table_key'];
		$table_handle = $_POST['external_users_table_handle'];
	
		// check if table exists
		$sql = "SHOW TABLES LIKE '$table'";
		$result = qa_db_query_sub($sql);
		$rows = qa_db_read_all_assoc($result);
		if (count($rows) == 0)
			$ret = false;
		else
		{
			// check if id column exists
			$sql = "SHOW COLUMNS FROM `$table` LIKE '$table_key'";
			$result = qa_db_query_sub($sql);
			$rows = qa_db_read_all_assoc($result);
			if (count($rows) == 0)
				$ret = false;
				
			// check if id column exists
			$sql = "SHOW COLUMNS FROM `$table` LIKE '$table_handle'";
			$result = qa_db_query_sub($sql);
			$rows = qa_db_read_all_assoc($result);
			if (count($rows) == 0)
				$ret = false;
		}
			
		return $ret;
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
		if ( !qa_opt($this->optactive) )
			return;

		// don't log 'ninja' edits (within 5 minutes)
		$now = time();
		$oldkey = $event == 'q_edit' ? 'oldquestion' : 'oldanswer';
		$lastupdate = $params[$oldkey]['updated'];
		// new posts have a NULL updated time
		if ( $lastupdate == null )
			$lastupdate = $params[$oldkey]['created'];
		if ( qa_opt('edit_history_NET') != 0 && (abs($now-$lastupdate) < qa_opt('edit_history_NET')) )
			return;

		$userid = qa_get_logged_in_userid();
		$sql =
			'INSERT INTO ^edit_history (postid, updated, title, content, tags, userid, reason) ' .
			'VALUES (#, NOW(), $, $, $, #, $)';

		return qa_db_query_sub( $sql, $params['postid'], @$params['oldtitle'], $params['oldcontent'], @$params['oldtags'], $userid, '' );
	}

}
