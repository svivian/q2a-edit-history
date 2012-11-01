<?php
/*
	Question2Answer Edit History plugin, v0.9
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_html_theme_layer extends qa_html_theme_base
{
	function post_meta($post, $class, $prefix=null, $separator='<BR/>')
	{
		if ( @$post['what_2'] == 'edited' )
		{
			$url = qa_path_to_root() . 'revisions/' . $post['raw']['postid'];
			$post['what_2'] = '<a rel="nofollow" href="'.$url.'" class="'.$class.'-revised">'.$post['what_2'].'</a>';
		}

		parent::post_meta($post, $class, $prefix, $separator);
	}

}
