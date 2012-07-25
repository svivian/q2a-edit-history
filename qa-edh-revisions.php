<?php
/*
	Question2Answer Edit History plugin, v0.1
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
				'title' => 'Post Revisions',
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

		// get postid
		preg_match( $this->reqmatch, $request, $matches );
		$postid = $matches[1];
		if ( !is_numeric($postid) )
			return;

		// get original post
		$sql = 'SELECT postid, type, userid, format, updated, title, content, tags FROM ^posts WHERE postid=#';
		$result = qa_db_query_sub( $sql, $postid );
		$original = qa_db_read_one_assoc( $result );
		$revisions = array( $original );

		// get post revisions
		$sql = 'SELECT postid, updated, title, content, tags, userid FROM ^edit_history WHERE postid=# ORDER BY updated DESC';
		$result = qa_db_query_sub( $sql, $postid );
		// TODO: return 404 if no revisions
		$revisions = array_merge( $revisions, qa_db_read_all_assoc( $result ) );

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
			// $revisions[$i]['diff_tags'] = diff_string::compare( qa_html($revisions[$i]['tags']), qa_html($revisions[$i-1]['tags']) );
		}

		echo '<pre style="text-align:left">', print_r($revisions,true), '</pre>';

		// display results
		$html = '';
		$options = array( 'blockwordspreg' => qa_get_block_words_preg() );
		// $viewer = qa_load_viewer( $revisions[0]['content'], 'html' );

		foreach ( $revisions as $rev )
		{
			// $view = qa_viewer_html($rev['diff'], $original['format'], $options);
			$html .= '<div class="diff-block">' . "\n";
			$html .= '  <div class="diff-date">Edited ' . $rev['updated'] . '</div>' . "\n";
			$html .= '  <h2>' . $rev['diff_title'] . '</h2>' . "\n";
			$html .= '  <div>' . nl2br($rev['diff_content']) . '</div>' . "\n";
			$html .= '</div>' . "\n\n";
		}

		$qa_content = qa_content_prepare();
		$qa_content['title'] = 'Edit history for post #'.$postid;

		// prevent search engines indexing revision pages
		$qh =& $qa_content['head_lines'];
		$qh[] = '<meta name="robots" content="noindex,follow">';
		$qh[] = '<style>';
		$qh[] = '.diff-block { padding-bottom: 20px; margin-bottom: 20px; /*border-bottom: 1px solid #ddd; font: 14px monospace;*/ } ';
		$qh[] = '.diff-date { background: #eee; padding: 2px; margin: 5px 0; } ';
		$qh[] = 'ins { background-color: #d1e1ad; color: #405a04; text-decoration: none; } ';
		$qh[] = 'del { background-color: #e5bdb2; color: #a82400; text-decoration: line-through; } ';
		$qh[] = '</style>';

		$qa_content['custom'] = $html;
		return $qa_content;
	}

}
