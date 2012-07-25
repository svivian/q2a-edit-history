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
		$sql = 'SELECT postid, edited, title, content, tags, userid FROM ^edit_history WHERE postid=# ORDER BY edited DESC';
		$result = qa_db_query_sub( $sql, $postid );
		// TODO: return 404 if no revisions
		$revisions = array_merge( $revisions, qa_db_read_all_assoc( $result ) );

		// run diff algorithm
		$revisions = array_reverse( $revisions );
		$revisions[0]['diff'] = $revisions[0]['content'];
		for ( $i = 1, $len = count($revisions); $i < $len; $i++ )
		{
			$revisions[$i]['diff'] = diff_string::compare( qa_html($revisions[$i]['content']), qa_html($revisions[$i-1]['content']) );
		}

// 		echo '<pre>', print_r($revisions,true), '</pre>';

		// display results
		$html =
			'<style>' .
			'.diff-block { font: 14px monospace; padding-bottom: 20px; border-bottom: 1px solid #ddd; margin-bottom: 20px; } ' .
			'ins { background-color: #d1e1ad; color: #405a04; text-decoration: none; } ' .
			'del { background-color: #e5bdb2; color: #a82400; text-decoration: line-through; } ' .
			'</style>';

		$style = '';
		$options = array( 'blockwordspreg' => qa_get_block_words_preg() );

// 		$viewer = qa_load_viewer( $revisions[0]['content'], 'html' );
		foreach ( $revisions as $rev )
		{
// 			$view = qa_viewer_html($rev['diff'], $original['format'], $options);
			$view = nl2br($rev['diff']);
			$html .= '<div class="diff-block" style="'.$style.'">' . $view . '</div>' . "\n\n";
		}


		$qa_content = qa_content_prepare();
		$qa_content['title'] = 'Edit history for post #'.$postid;
		$qa_content['custom'] = $html;
		return $qa_content;
	}

}
