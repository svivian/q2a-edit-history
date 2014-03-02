<?php
/*
	Question2Answer Edit History plugin
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_edh_revisions
{
	private $directory;
	private $urltoroot;
	private $reqmatch = '#revisions(/([0-9]+))?$#';

	public function load_module( $directory, $urltoroot )
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function suggest_requests()
	{
		return array(
			array(
				'title' => qa_lang_html('edithistory/request_title'),
				'request' => 'revisions',
				'nav' => null,
			),
		);
	}

	public function match_request( $request )
	{
		// validates the postid so we don't need to do this later
		return preg_match( $this->reqmatch, $request ) > 0;
	}

	public function process_request( $request )
/*
	Post edits are stored in a special way. The `qa_posts` tables contains the latest version displayed (obviously).
	The `qa_edit_history` table stores each previous revision, with the time it was updated to the later one.
	So each time applies to the next revision, with `qa_posts.created` being when the first revision from
	`qa_edit_history` was posted.
*/
	{
		require $this->directory.'class.diff-string.php';
		$qa_content = qa_content_prepare();
		preg_match( $this->reqmatch, $request, $matches );

		if ( isset($matches[2]) )
		{
			// post revisions: list all edits to this post
			$this->post_revisions( $qa_content, qa_html($matches[2]) );
		}
		else
		{
			// main page: list recent revisions
			$this->recent_edits( $qa_content );
		}

		return $qa_content;
	}

	// Display all recent edits
	private function recent_edits( &$qa_content )
	{
		$qa_content['title'] = qa_lang_html('edithistory/main_title');
		$qa_content['custom'] = qa_lang_html('edithistory/page_description');
	}

	// Display all the edits made to a post ($postid already validated)
	private function post_revisions( &$qa_content, $postid )
	{
		$qa_content['title'] = qa_lang_html_sub('edithistory/revision_title', $postid);

		if (qa_user_permit_error('edit_history_view_permission'))
		{
			$qa_content['error'] = qa_lang_html('edithistory/permission_error');
			return null;
		}			

		$sql = '';
		
		// get original post
		if(QA_FINAL_EXTERNAL_USERS && (bool)qa_opt('edit_history_EEU'))
		{
			$sql =
				'SELECT p.postid, p.type, p.userid, u.' . qa_opt('edit_history_EUTH') . ' as handle, p.format, UNIX_TIMESTAMP(p.created) AS updated, p.title, p.content, p.tags
				 FROM ^posts p LEFT JOIN ' . qa_opt('edit_history_EUT') . ' u ON u.' . qa_opt('edit_history_EUTK') . '=p.userid
				 WHERE p.postid=#';
			$result = qa_db_query_sub( $sql, $postid );
		}
		else
		{
			$sql =
				'SELECT p.postid, p.type, p.userid, u.handle, p.format, UNIX_TIMESTAMP(p.created) AS updated, p.title, p.content, p.tags
				 FROM ^posts p LEFT JOIN ^users u ON u.userid=p.userid
				 WHERE p.postid=#';
			$result = qa_db_query_sub( $sql, $postid );
		}
		$original = qa_db_read_one_assoc( $result, true );
		$revisions = array( $original );

		// get post revisions
		if(QA_FINAL_EXTERNAL_USERS && (bool)qa_opt('edit_history_EEU'))
		{
			$sql =
				'SELECT p.postid, p.userid, u.' . qa_opt('edit_history_EUTH') . ' as handle, UNIX_TIMESTAMP(p.updated) AS updated, p.title, p.content, p.tags
				 FROM ^edit_history p LEFT JOIN ' . qa_opt('edit_history_EUT') . ' u ON u.' . qa_opt('edit_history_EUTK') . '=p.userid
				 WHERE p.postid=#
				 ORDER BY p.updated DESC';
			$result = qa_db_query_sub( $sql, $postid );
		}
		else
		{
			$sql =
				'SELECT p.postid, p.userid, u.handle, UNIX_TIMESTAMP(p.updated) AS updated, p.title, p.content, p.tags
				 FROM ^edit_history p LEFT JOIN ^users u ON u.userid=p.userid
				 WHERE p.postid=#
				 ORDER BY p.updated DESC';
			$result = qa_db_query_sub( $sql, $postid );
		}
		$revisions = array_merge( $revisions, qa_db_read_all_assoc( $result ) );

		// return 404 if no revisions
		if ( !$original || count($revisions) <= 1 )
		{
			header('HTTP/1.0 404 Not Found');
			$qa_content['error'] = qa_lang_html('edithistory/no_revisions');
			return $qa_content;
		}

		// censor posts
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		$options = array( 'blockwordspreg' => qa_get_block_words_preg(), 'fulldatedays' => qa_opt('show_full_date_days') );
		foreach ( $revisions as &$rev )
		{
			$rev['title'] = qa_block_words_replace( $rev['title'], $options['blockwordspreg'] );
			$rev['content'] = qa_block_words_replace( $rev['content'], $options['blockwordspreg'] );
		}

		// run diff algorithm
		$revisions = array_reverse( $revisions );
		$revisions[0]['diff_title'] = $revisions[0]['title'];
		$revisions[0]['diff_content'] = $revisions[0]['content'];
		$len = count($revisions);

		for ( $i = 1; $i < $len; $i++ )
		{
			$rc =& $revisions[$i];
			$rp =& $revisions[$i-1];
			$rc['diff_title'] = (new diff_string)->compare( qa_html($rp['title']), qa_html($rc['title']) );
			$rc['diff_content'] = (new diff_string)->compare( qa_html($rp['content']), qa_html($rc['content']) );
			$rc['edited'] = $rp['updated'];
			$rc['editedby'] = $this->user_handle( $rp['handle'] );
		}
		$revisions[0]['edited'] = $revisions[$len-1]['updated'];
		$revisions[0]['editedby'] = $this->user_handle( $revisions[$len-1]['handle'] );

		// $revisions = array_reverse( $revisions );

		// display results
		$post_url = null;
		if ( $original['type'] == 'Q' )
			$post_url = qa_q_path_html( $original['postid'], $original['title'] );
		else if ( $original['type'] == 'A' )
			$post_url = '';

		$html = $post_url ? '<p><a href="' . $post_url . '">&laquo; ' . qa_lang_html('edithistory/back_to_post') . '</a></p>' : '';
		foreach ( $revisions as $i=>$rev )
		{
			$updated = implode( '', qa_when_to_html($rev['edited'], $options['fulldatedays']) );
			if ( $i > 0 )
			{
				$edited_when_by = strtr(qa_lang_html('edithistory/edited_when_by'), array(
					'^1' => $updated,
					'^2' => $rev['editedby'],
				));
			}
			else
			{
				$edited_when_by = qa_lang_html_sub('edithistory/original_post_by', $rev['editedby']);
				$edited_when_by = strtr(qa_lang_html('edithistory/original_post_by'), array(
					'^1' => $updated,
					'^2' => $rev['editedby'],
				));
			}

			$html .= '<div class="diff-block">' . "\n";
			$html .= '  <div class="diff-date">' . $edited_when_by . '</div>' . "\n";
			$html .= '  <h2>' . $rev['diff_title'] . '</h2>' . "\n";
			$html .= '  <div>' . nl2br($rev['diff_content']) . '</div>' . "\n";
			$html .= '</div>' . "\n\n";
		}

		// prevent search engines indexing revision pages
		$qh =& $qa_content['head_lines'];
		$qh[] = '<meta name="robots" content="noindex,follow">';
		// styles for this page
		$qh[] = '<style>';
		$qh[] = '.diff-block { padding-bottom: 20px; margin-bottom: 20px; } ';
		$qh[] = '.diff-date { margin: 5px 0; padding: 3px 6px; background: #eee; color: #000; } ';
		$qh[] = 'ins { background-color: #d1e1ad; color: #405a04; text-decoration: none; } ';
		$qh[] = 'del { background-color: #e5bdb2; color: #a82400; text-decoration: line-through; } ';
		$qh[] = '</style>';

		$qa_content['custom'] = $html;
	}

	private function user_handle($handle)
	{
		$url = qa_path_html('', array('qa'=>'user/'.qa_html($handle)));
		return $handle === null ? qa_lang_html('main/anonymous') : '<a rel="nofollow" href="'.$url.'">' . qa_html($handle) . '</a>';
	}
}
