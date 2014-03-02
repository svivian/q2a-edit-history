<?php
/*
	Question2Answer Edit History plugin
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_html_theme_layer extends qa_html_theme_base
{
	private $rev_postids = array();

	function doctype()
	{
		$q_tmpl = $this->template == 'question';
		$qa_exists = isset($this->content['q_view']) && isset($this->content['a_list']);

		$user_permit = !qa_user_permit_error('edit_history_view_permission');
		
		if ( $q_tmpl && $qa_exists && $user_permit )
		{
			$this->content['q_view']['form']['buttons']['edit']['tags'] .= ' onclick="return edit_check(' . $this->content['q_view']['raw']['postid'] . ');"';

			// grab a list of all Q/A posts on this page
			$postids = array( $this->content['q_view']['raw']['postid'] );
			foreach ( $this->content['a_list']['as'] as $key=>$answ )
			{
				$this->content['a_list']['as'][$key]['form']['buttons']['edit']['tags'] .= ' onclick="return edit_check(' . $answ['raw']['postid'] . ');"';
				$postids[] = $answ['raw']['postid'];
			}

			$sql = 'SELECT postid, MAX(UNIX_TIMESTAMP(updated)) AS last_update FROM ^edit_history WHERE postid IN (' . implode(', ', $postids) . ') GROUP BY postid';
			$result = qa_db_read_all_assoc( qa_db_query_sub($sql) );
			foreach($result as $row)
				$this->rev_postids[$row['postid']] = $row['last_update'];
		}

		parent::doctype();
	}

	function head_script()
	{		
		$json_data = '{';
		foreach($this->rev_postids as $postid=>$date)
			$json_data .= "'$postid':'$date',";
		$json_data .= '}';
		
		$this->output_raw('<script type="text/javascript">');
		$this->output_raw("var revisions = $json_data;");
		$this->output_raw("var lock_time = " . qa_opt('edit_history_NET') . ";");
		$this->output_raw('var edit_check = function(postid){');
		$this->output_raw('var now = new Date().getTime()/1000;');
		$this->output_raw('if(Math.round(now)-revisions[postid]>lock_time){');
		$this->output(
			'var id = "#meta-message-" + postid;',
			'var msg = $(id);',
			'msg.toggle(500);',
			'setTimeout(function(){msg.toggle(500);},5000);',
			'msg.html("' . qa_lang_html_sub('edithistory/edit_locked', qa_opt('edit_history_NET')) . '");'
		);
		$this->output_raw('return false;');
		$this->output_raw('}');
		$this->output_raw('return true;');
		$this->output_raw('}');
		$this->output_raw('</script>');

		parent::head_script();
	}

	function post_meta($post, $class, $prefix=null, $separator='<br />')
	{
		// only link when there are actual revisions
		if ( isset($post['when_2']) && in_array( $post['raw']['postid'], array_keys($this->rev_postids) ) )
		{
			$url = qa_path_html("revisions", array("qa_1"=>$post['raw']['postid']));
			$post['when_2']['data'] = '<a rel="nofollow" href="'.$url.'" class="'.$class.'-revised">' . $post['when_2']['data'] . '</a>';
		}

		parent::post_meta($post, $class, $prefix, $separator);
	}
	function post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<br />')
	{
		$this->output('<span class="'.$class.'-avatar-meta">');
		$this->post_avatar($post, $class, $avatarprefix);
		$this->post_meta($post, $class, $metaprefix, $metaseparator);
		$this->output('<span class="meta-message" id="meta-message-' . $post['raw']['postid'] . '" style="color: red; font-size; 10px; display: none;">');
		$this->output('</span>');
		$this->output('</span>');
	}

}
