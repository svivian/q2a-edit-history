<?php
/*
	Question2Answer Edit History plugin, v0.9
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_edh_revisions
{
	private $directory;
	private $urltoroot;
	private $reqmatch = '#revisions/([0-9]+)#';

	function load_module( $directory, $urltoroot )
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	function suggest_requests() // for display in admin interface
	{
		return array(
			array(
				'title' => qa_lang_html('edithistory/admin_title'),
				'request' => 'revisions',
				'nav' => null, // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}

	function match_request( $request )
	{
		return preg_match( $this->reqmatch, $request ) > 0;
	}

	function process_request( $request )
	{
		require $this->directory.'class.diff-string.php';

		// get postid (already validated in `match_request`)
		preg_match( $this->reqmatch, $request, $matches );
		$postid = $matches[1];

		// set up page content
		$qa_content = qa_content_prepare();
		$qa_content['title'] = qa_lang_html_sub('edithistory/revision_title', $postid);

		// get original post
		$sql = 'SELECT postid, type, userid, format, UNIX_TIMESTAMP(updated) AS updated, title, content, tags FROM ^posts WHERE postid=#';
		$result = qa_db_query_sub( $sql, $postid );
		$original = qa_db_read_one_assoc( $result, true );
		$revisions = array( $original );


		// get post revisions
		$sql = 'SELECT postid, UNIX_TIMESTAMP(updated) AS updated, title, content, tags, userid FROM ^edit_history WHERE postid=# ORDER BY updated DESC';
		$result = qa_db_query_sub( $sql, $postid );
		$revisions = array_merge( $revisions, qa_db_read_all_assoc( $result ) );

		// return 404 if no revisions
		if ( !$original || count($revisions) <= 1 )
		{
			header('HTTP/1.0 404 Not Found');
			$qa_content['error'] = qa_lang_html('edithistory/no_revisions');
			return $qa_content;
		}

		// censor posts
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
		for ( $i = 1, $len = count($revisions); $i < $len; $i++ )
		{
			$rc =& $revisions[$i];
			$rp =& $revisions[$i-1];
			$rc['diff_title'] = diff_string::compare( qa_html($rp['title']), qa_html($rc['title']) );
			$rc['diff_content'] = diff_string::compare( qa_html($rp['content']), qa_html($rc['content']) );
		}

		// display results
		$post_url = null;
		if ( $original['type'] == 'Q' )
			$post_url = qa_q_path_html( $original['postid'], $original['title'] );
		else if ( $original['type'] == 'A' )
			$post_url = '';

		$html = $post_url ? '<p><a href="' . $post_url . '">&laquo; ' . qa_lang_html('edithistory/back_to_post') . '</a></p>' : '';
		foreach ( $revisions as $rev )
		{
			$updated = qa_when_to_html($rev['updated'], $options['fulldatedays']);
			$html .= '<div class="diff-block">' . "\n";
			$html .= '  <div class="diff-date">' . qa_lang_html_sub('edithistory/edited_time', implode('', $updated)) . '</div>' . "\n";
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
		$qh[] = '.diff-date { background: #eee; padding: 2px; margin: 5px 0; } ';
		$qh[] = 'ins { background-color: #d1e1ad; color: #405a04; text-decoration: none; } ';
		$qh[] = 'del { background-color: #e5bdb2; color: #a82400; text-decoration: line-through; } ';
		$qh[] = '</style>';

		$qa_content['custom'] = $html;
		return $qa_content;
	}

}
