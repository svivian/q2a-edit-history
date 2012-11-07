<?php
/*
	Question2Answer Edit History plugin, v0.9
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_html_theme_layer extends qa_html_theme_base
{
	private $rev_postids = array();

	function doctype()
	{
		if ( $this->template == 'question' && isset($this->content['q_view']) && isset($this->content['a_list']) )
		{
			// grab a list of all Q/A posts on this page
			$postids = array( $this->content['q_view']['raw']['postid'] );
			foreach ( $this->content['a_list']['as'] as $answ )
				$postids[] = $answ['raw']['postid'];

			// check for revisions in these posts
			$sql = 'SELECT DISTINCT postid FROM ^edit_history WHERE postid IN (' . implode(', ', $postids) . ')';
			$this->rev_postids = qa_db_read_all_values( qa_db_query_sub($sql) );
		}

		parent::doctype();
	}

	function post_meta($post, $class, $prefix=null, $separator='<BR/>')
	{
		// only link when there are actual revisions
		if ( isset($post['when_2']) && in_array( $post['raw']['postid'], $this->rev_postids ) )
		{
			$url = qa_path_to_root() . 'revisions/' . $post['raw']['postid'];
			$post['when_2']['data'] = '<a rel="nofollow" href="'.$url.'" class="'.$class.'-revised">' . $post['when_2']['data'] . '</a>';
		}

		parent::post_meta($post, $class, $prefix, $separator);
	}

}
