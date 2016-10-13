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
	private $options; // stores relevant options

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function suggest_requests()
	{
		return array(
			array(
				'title' => 'Edit History',
				'request' => 'revisions',
				'nav' => null,
			),
		);
	}

	public function match_request($request)
	{
		// validates the postid so we don't need to do this later
		return preg_match($this->reqmatch, $request) > 0;
	}

	public function process_request($request)
	{
		require $this->directory.'class.diff-string.php';
		$qa_content = qa_content_prepare();
		preg_match($this->reqmatch, $request, $matches);

		if (isset($matches[2]))
		{
			$revertid = qa_post_text('revert');
			$deleteid = qa_post_text('delete');
			// revert a revision
			if ($revertid !== null)
				$this->revert_revision($qa_content, $matches[2], $revertid);
			// delete a revision
			else if ($deleteid !== null)
				$this->delete_revision($qa_content, $matches[2], $revertid);
			// post revisions: list all edits to this post
			else
				$this->post_revisions($qa_content, $matches[2]);
		}
		// main page: list recent revisions
		else
		{
			// main page: list recent revisions
			$this->recent_edits($qa_content);

			$days = 3; // last days to show from today

			// set language string herefile: qa_lang_html('edithistory/revision_list_title');
			$qa_content['title'] = 'Revision list (last '.$days.' days)';

			// for admin options to delete revision entry
			$isadmin = (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN);

			// delete edit entry of post that is passed by admin
			if($isadmin && isset($_GET['delete']) && isset($_GET['updated']))
			{
				$postid = $_GET['delete'];
				$updated = $_GET['updated'];
				qa_db_query_sub('DELETE FROM ^edit_history
									WHERE postid = $
									AND updated = $
								', $postid, $updated);
				$qa_content['custom'] = '
					<p>
						Edit deleted: '.$postid.' / timestamp: '.$updated.'
					</p>
					<p>
						<a href="/revisions/">back to revisions</a>
					</p>
				';
				return $qa_content;
			}

			$revisiontable = '
				<table id="revisiontable">
					<thead>
						<th>Time</th> <th>Question edited</th> <th>Editor</th>
					</thead>
			';

			// get list of posts
			$queryrevisionlist = qa_db_query_sub('SELECT postid, updated, title, tags, userid FROM `^edit_history`
											WHERE updated > DATE_ADD(NOW(), INTERVAL -'.$days.' DAY)
											ORDER BY updated DESC
										  ');

			while( ($row = qa_db_read_one_assoc($queryrevisionlist,true)) !== null )
			{
				// get link to revision post
				$activity_url = qa_path('revisions').'/'.$row['postid'];
				// get link to original question
				$post_url = qa_path_html( qa_q_request( $row['postid'], $row['title'] ) );

				// workaround to get link to answer
				$display_post_url = '';
				if(!empty($post_url))
				{
					// get new title of question
					$qTitle = qa_db_read_one_value( qa_db_query_sub("SELECT title FROM `^posts` WHERE `postid` = ".$row['postid']." LIMIT 1"), true );
					// get correct public URL
					$question_url = qa_path_html(qa_q_request($row['postid'], $qTitle), null, qa_opt('site_url'), null, null);
					// frontend
					$display_post_url = 'RECENT: <a target="_blank" href="'.$question_url.'">'.$qTitle.'</a>';
				}

				// skip if empty title
				if(empty($row['title']))
				{
					// do not show admin edits
					continue;
				}
				// anonymous user
				else if(empty($row['userid']))
				{
					$revisionlink = isset($row['title']) ? $row['title'] : "Answer was edited";
					// display date as before x time
					$updatetime = implode('', qa_when_to_html( strtotime($row['updated']), qa_opt('show_full_date_days')));
					$revisiontable .= '
						<tr>
							<td>'.$updatetime.'</td>
							<td style="font-size:11px;">
								<a target="_blank" href="'.$activity_url.'">'.$revisionlink.'</a>
								<br />
								'.$display_post_url.'
							</td>
							<td>
								anonymous
							</td>
						</tr>
					';
				}
				else
				{
					// admin
					$deleteoption = '';
					if($isadmin)
					{
						$deleteoption = '<br /><a style="font-size:10px;color:#F99;" href="/revisions/?delete='.$row['postid'].'&updated='.$row['updated'].'">delete entry</a>';
					}
					// get user details (display avatar and name)
					$username = qa_db_read_all_assoc(qa_db_query_sub('SELECT handle FROM ^users WHERE userid = #',$row['userid']), 'handle');
					$user = qa_db_select_with_pending( qa_db_user_account_selectspec($username, false) );
					$revisionlink = isset($row['title']) ? $row['title'] : "Answer was edited";
					// display date as before x time
					$updatetime = implode('', qa_when_to_html( strtotime($row['updated']), qa_opt('show_full_date_days')));
					$revisiontable .= '
						<tr>
							<td style="font-size:11px;">
								'.$updatetime.'
							</td>
							<td style="font-size:11px;">
								<a style="color:#C55;" target="_blank" href="'.$activity_url.'">REVISION: '.$revisionlink.'</a>
								<br />
								'.$display_post_url.'
							</td>
							<td style="font-size:11px;">
								'. qa_get_user_avatar_html($user['flags'], $user['email'], $user['handle'], $user['avatarblobid'], $user['avatarwidth'], $user['avatarheight'], qa_opt('avatar_users_size'), false) . " " . qa_get_one_user_html($user['handle'], false). $deleteoption . '
							</td>
						</tr>
					';
				}
			}

			$revisiontable .= '</table>';

			// revision list
			$qa_content['custom'] .= '
				<div class="revisionlist" style="border-radius:0; margin-top:-2px; min-height:350px;">
					'.$revisiontable.'
				</div> <!-- revisionlist -->
			';

			// custom css for qa-main
			$qa_content['custom'] .= '
				<style type="text/css">
					#revisiontable {width:98%;}
					#revisiontable thead, #revisiontable th { border:1px solid #CCC; background:#FFC; padding:8px; }
					#revisiontable td { padding:8px; border:1px solid #CCC; }
					#revisiontable tr:nth-of-type(even){ background-color:#DDD }
				</style>
			';
		}

		return $qa_content;
	} // end process_request


	// Display all recent edits
	private function recent_edits(&$qa_content)
	{
		$qa_content['title'] = qa_lang_html('edithistory/plugin_title');

		// check user is allowed to view edit history
		if (!$this->view_permit($qa_content))
			return;

		$qa_content['title'] = qa_lang_html('edithistory/main_title');
		$qa_content['custom'] = '<p>This page will list posts that have been edited recently.</p>';
	}

	private function post_revisions(&$qa_content, $postid)
/*
	Display all the edits made to a post ($postid already validated).
	Post edits are stored in a special way:
	- The `qa_posts` table contains the latest version displayed, as expected. The `qa_edit_history`
	  table stores each previous revision, with the time and user of the later one.
	- Each time applies to the next revision, with `qa_posts.created` being when the first revision
	  from `qa_edit_history` was posted. The time of the latest revision should match
	  `qa_posts.updated`.
	- Similarly for users, `qa_posts.userid` is the author of the first revision, while
	  `qa_edit_history.userid` specifies who made the next revision. The userid of the latest
	  revision should match `qa_posts.lastuserid`.
*/
	{
		$qa_content['title'] = qa_lang_html('edithistory/plugin_title');

		// check user is allowed to view edit history
		if (!$this->view_permit($qa_content))
			return;

		// get revisions from oldest to newest
		$revisions = $this->db_get_revisions($postid);

		// return 404 if no revisions
		if (count($revisions) <= 1)
		{
			header('HTTP/1.0 404 Not Found');
			$qa_content['error'] = qa_lang_html('edithistory/no_revisions');
			return $qa_content;
		}

		// censor posts; build list of userids as we go
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		$this->options = array(
			'blockwordspreg' => qa_get_block_words_preg(),
			'fulldatedays' => qa_opt('show_full_date_days'),
		);
		$userids = array();

		foreach ($revisions as &$rev)
		{
			$rev['title'] = qa_block_words_replace( $rev['title'], $this->options['blockwordspreg'] );
			$rev['content'] = qa_block_words_replace( $rev['content'], $this->options['blockwordspreg'] );
			if (!in_array($rev['userid'], $userids))
				$userids[] = $rev['userid'];
		}

		// get user handles
		$usernames = qa_userids_to_handles($userids);

		// set diff of oldest revision to its own content
		$revisions[0]['id'] = 0;
		$revisions[0]['diff_title'] = trim($revisions[0]['title']);
		$revisions[0]['diff_content'] = $revisions[0]['content'];
		$revisions[0]['handle'] = $usernames[$revisions[0]['userid']];
		$len = count($revisions);

		// run diff algorithm against each previous revision in turn
		for ($i = 1; $i < $len; $i++)
		{
			$rc =& $revisions[$i];
			$rp =& $revisions[$i-1];

			$rc['id'] = $i;
			$rc['diff_title'] = trim( diff_string::compare(qa_html($rp['title']), qa_html($rc['title'])) );
			$rc['diff_content'] = null;
			if ($rp['content'] !== $rc['content'])
				$rc['diff_content'] = trim( diff_string::compare(qa_html($rp['content']), qa_html($rc['content'])) );

			$rc['edited'] = $rp['updated'];
			$rc['editedby'] = $rp['handle'];

			$rc['handle'] = $usernames[$rc['userid']];
		}
		$revisions[0]['edited'] = $revisions[$len-1]['updated'];
		$revisions[0]['editedby'] = $revisions[$len-1]['handle'];

		// display results
		$revisions = array_reverse($revisions);
		$this->html_output($qa_content, $revisions, $postid);
	}

	// check page viewing permissions (handles error messages)
	private function view_permit(&$qa_content)
	{
		$error = qa_user_permit_error('edit_history_view_perms');
		if ($error === 'login')
		{
			$qa_content['error'] = qa_insert_login_links( qa_lang_html('edithistory/need_login'), qa_request() );
			return false;
		}
		else if ($error !== false)
		{
			$qa_content['error'] = qa_lang_html('edithistory/no_user_perms');
			return false;
		}

		return true;
	}

	// check revert/delete permissions
	private function admin_permit()
	{
		return qa_user_permit_error('edit_history_admin_perms') === false;
	}

	// return array containing the post at each revision, oldest first
	private function db_get_revisions($postid)
	{
		// get previous revisions from qa_edit_history
		$sql =
			'SELECT postid, userid, UNIX_TIMESTAMP(updated) AS updated, title, content, tags
			 FROM ^edit_history
			 WHERE postid=#
			 ORDER BY updated';
		$result = qa_db_query_sub($sql, $postid);
		$revisions = qa_db_read_all_assoc($result);

		// get latest version of post from qa_posts
		$sql =
			'SELECT postid, type, parentid, userid, format, UNIX_TIMESTAMP(created) AS updated, title, content, tags
			 FROM ^posts
			 WHERE postid=#';
		$result = qa_db_query_sub($sql, $postid);
		$current = qa_db_read_one_assoc($result, true);

		return array_merge($revisions, array($current));
	}

	private function html_output(&$qa_content, &$revisions, $postid)
	{
		$html = '<form action="' . qa_path_html('revisions/'.$postid) . '" method="post">';

		// create link back to post
		$currRev = $revisions[0];
		if ($currRev['type'] == 'Q')
			$posturl = qa_q_path_html($currRev['postid'], $currRev['title']);
		else if ($currRev['type'] == 'A')
			$posturl = qa_q_path_html($currRev['parentid'], $currRev['title'], false, 'A', $currRev['postid']);

		if (!empty($posturl))
			$html .= '<p><a href="' . $posturl . '">' . qa_lang_html('edithistory/back_to_post') . '</a></p>';

		$show_buttons = $this->admin_permit();

		$num_revs = count($revisions);
		foreach ($revisions as $i=>$rev)
		{
			$updated = implode( '', qa_when_to_html($rev['edited'], $this->options['fulldatedays']) );
			$userlink = $this->user_handle_link($rev['editedby']);
			$langkey = $i < $num_revs-1 ? 'edithistory/edited_when_by' : 'edithistory/original_post_by';

			$edited_when_by = strtr(qa_lang_html($langkey), array(
				'^1' => $updated,
				'^2' => $userlink,
			));

			$html .= '<div class="diff-block">' . "\n";
			$html .= '  <div class="diff-date">';
			if ($i == 0)
				$html .= '<span class="diff-button">' . qa_lang_html('edithistory/current_revision') . '</span>';
			else if ($show_buttons) {
				// $html .= '<button type="submit" name="delete" value="' . $rev['id'] . '" class="diff-button qa-form-tall-button qa-form-tall-button-cancel">' .
				// 	qa_lang('edithistory/delete') . '</button>';
				$html .= '<button type="submit" name="revert" value="' . $rev['id'] . '" class="diff-button qa-form-tall-button qa-form-tall-button-reset">' .
					qa_lang('edithistory/revert') . '</button>';
			}
			$html .= $edited_when_by;
			$html .= '</div>' . "\n";

			$html .= '  <div class="diff-content">';
			if (!empty($rev['diff_title']))
				$html .= '    <p class="h2">' . $rev['diff_title'] . '</p>' . "\n";
			if ($rev['diff_content'] !== null)
				$html .= '    <div>' . nl2br($rev['diff_content']) . '</div>' . "\n";
			else
				$html .= '    <div class="no-diff">' . qa_lang_html('edithistory/content_unchanged') . '</div>' . "\n";
			$html .= '</div>' . "\n";
			$html .= '</div>' . "\n\n";
		}

		$html .= '</form>' . "\n\n";

		$qh =& $qa_content['head_lines'];
		// prevent search engines indexing revision pages
		$qh[] = '<meta name="robots" content="noindex,follow">';
		// styles for this page
		$csslines = file_get_contents($this->directory.'revisions.css');
		$mincss = preg_replace('~\s+~', ' ', $csslines);
		$qh[] = '<style>';
		$qh[] = $mincss;
		$qh[] = '</style>';

		$qa_content['script_onloads'][] = array(
			'$("button[name=revert]").click(function() {',
			'	return window.confirm("' . qa_lang_html('edithistory/revert_warning') . '");',
			'});',
			'$("button[name=delete]").click(function() {',
			'	return window.confirm("' . qa_lang_html('edithistory/delete_warning') . '");',
			'});',
		);

		$qa_content['title'] = qa_lang_html_sub('edithistory/revision_title', $postid);
		$qa_content['custom'] = $html;
	}

	// roll back post to an earlier version
	private function revert_revision(&$qa_content, $postid, $revid)
	{
		if (!$this->admin_permit())
		{
			$qa_content['error'] = qa_lang_html('edithistory/no_user_perms');
			return false;
		}

		require_once QA_INCLUDE_DIR.'qa-app-posts.php';
		$revisions = $this->db_get_revisions($postid);

		qa_post_set_content($postid, $revisions[$revid]['title'], $revisions[$revid]['content'], null, null, null, null, qa_get_logged_in_userid());
		qa_redirect('revisions/'.$postid);
	}

	// delete an old revision
	private function delete_revision(&$qa_content, $postid, $revid)
	{
		if (!$this->admin_permit())
		{
			$qa_content['error'] = qa_lang_html('edithistory/no_user_perms');
			return false;
		}

		// require_once QA_INCLUDE_DIR.'qa-app-posts.php';
		// $revisions = $this->db_get_revisions($postid);
		// qa_post_set_content($postid, $revisions[$revid]['title'], $revisions[$revid]['content']);
		// qa_redirect('revisions/'.$postid);
	}

	private function user_handle_link($handle)
	{
		return empty($handle)
			? qa_lang_html('main/anonymous')
			: '<a href="' . qa_path_html('user/'.$handle) . '">' . qa_html($handle) . '</a>';
	}

}
