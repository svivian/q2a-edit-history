<?php
/*
	Question2Answer Edit History plugin, v0.1
	License: http://www.gnu.org/licenses/gpl.html
*/

require_once QA_INCLUDE_DIR.'qa-app-users.php';

class qa_edit_history
{
	// private $directory;
	// private $urltoroot;

	private $opt = 'edit_history_active';

	// public function load_module($directory, $urltoroot)
	// {
	// 	$this->directory = $directory;
	// 	$this->urltoroot = $urltoroot;
	// }

	function admin_form( &$qa_content )
	{
		$saved_msg = '';

		if ( qa_clicked('edit_history_activate_button') )
		{
			// create table
			$sql_create =
				'CREATE TABLE IF NOT EXISTS ^edit_history ( ' .
				'`postid` int(10) unsigned NOT NULL, ' .
				'`edited` datetime NOT NULL, ' .
				'`title` varchar(800) DEFAULT NULL, ' .
				'`content` varchar(8000) DEFAULT NULL, ' .
				'`tags` varchar(800) DEFAULT NULL, ' .
				'`userid` int(10) unsigned DEFAULT NULL, ' .
				'`reason` varchar(800) DEFAULT NULL, ' .
				'PRIMARY KEY (`postid`,`edited`) ' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8;';
			$result = qa_db_query_sub($sql_create);

			if ( $result === true )
			{
				qa_opt( $this->opt, '1' );
				$saved_msg = 'Plugin activated!';
			}
		}

		if ( !qa_opt($this->opt) )
		{
			return array(
				'buttons' => array(
					array(
						'label' => 'Activate Edit History',
						'tags' => 'name="edit_history_activate_button"',
						'note' => '(not set up yet)',
					),
				),
			);
		}

		return array(
			'ok' => $saved_msg,
		);

	}


	function process_event( $event, $userid, $handle, $cookieid, $params )
	{
		// only interested in edits
		$attachevents = array('q_edit', 'a_edit');
		if ( !in_array( $event, $attachevents ) )
			return;

		// get config data
		if ( !qa_opt($this->opt) )
			return;

		// TODO: don't store ninja edits
		$userid = qa_get_logged_in_userid();

		$sql =
			'INSERT INTO ^edit_history (postid, edited, title, content, tags, userid, reason) ' .
			'VALUES (#, NOW(), $, $, $, #, $)';

		return qa_db_query_sub( $sql, $params['postid'], $params['oldtitle'], $params['oldcontent'], $params['oldtags'], $userid, '' );
	}

}
