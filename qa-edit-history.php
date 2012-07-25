<?php
/*
	Question2Answer Edit History plugin, v0.1
	License: http://www.gnu.org/licenses/gpl.html
*/

require_once QA_INCLUDE_DIR.'qa-app-users.php';

class qa_edit_history
{
	private $pluginkey = 'edit_history';
	private $optactive = 'edit_history_active';

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
				{
					qa_opt( $this->optactive, '1' );
				}
				else
				{
					$error = array(
						'type' => 'custom',
						'error' => 'Database table is not set up yet. <a href="' . qa_path('install') . '">Create table</a>',
					);
				}

				$saved_msg = 'Changes saved.';
			}
			else
				qa_opt( $this->optactive, '0' );
		}

		$eh_active = qa_opt($this->optactive);

		$form = array(
			'ok' => $saved_msg,

			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => 'Edit History active',
					'tags' => 'NAME="eh_active"',
					'value' => $eh_active === '1',
					'note' => 'Untick to stop tracking post edits.',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save changes',
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
		if ( $event == 'q_edit' && $params['title'] == $params['oldtitle'] && $params['content'] == $params['oldcontent'] )
			return;

		// answer content was not changed
		if ( $event == 'a_edit' && $params['content'] == $params['oldcontent'] )
			return;

		// check if tracking is active
		if ( !qa_opt($this->optactive) )
			return;

		// TODO: don't log ninja edits
		// $params['oldquestion']['updated'];
		// $params['oldanswer']['updated'];

		$userid = qa_get_logged_in_userid();

		$sql =
			'INSERT INTO ^edit_history (postid, updated, title, content, tags, userid, reason) ' .
			'VALUES (#, NOW(), $, $, $, #, $)';

		return qa_db_query_sub( $sql, $params['postid'], @$params['oldtitle'], $params['oldcontent'], @$params['oldtags'], $userid, '' );
	}

}
