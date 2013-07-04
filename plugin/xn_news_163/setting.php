<?php

$error = $input = array();

$start = intval(core::gpc('start', 'R'));
$fid = intval(core::gpc('fid', 'R'));

if(!$this->form_submit() && empty($start)) {
	
	$input = array();
	$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
	$this->view->assign('forumoptions', $forumoptions);
	
	$this->view->assign('dir', $dir);
	$this->view->assign('input', $input);
	$this->view->display('xn_threadlist_thumb.htm');
} else {
	
	$limit = DEBUG ? 1 : 200;
	$threadlist = $this->thread->index_fetch(array('fid'=>$fid, 'tid'=>array('>'=>$start)), array('tid'=>1), 0, $limit);
	if(!empty($threadlist)) {
		$thread_return = array();
		foreach($threadlist as $thread) {
			$fid = $thread['fid'];
			$tid = $thread['tid'];
			$pid = $thread['firstpid'];
			$thread['coverimg'] = '';
			if($thread['imagenum'] > 0) {
				$attachlist = $this->attach->index_fetch(array('fid'=>$fid, 'pid'=>$pid), array(), 0, 20);
				foreach($attachlist as $k=>$attach) {
					if($attach['isimage'] == 1) {
						$thread['coverimg'] = image::thumb_name($attach['filename']);
						break;
					}
				}
			}
			
			$firstpost = $this->post->read($thread['fid'], $thread['firstpid']);
			$message = htmlspecialchars(strip_tags($firstpost['message']));
			$message = str_replace(array('&nbsp;', '&amp;'), array(' ', '&'), $message);
			$thread['brief'] = utf8::cutstr_cn($message, 200);
			$this->thread->update($thread);
		}
		
		$start = $thread['tid'];
		$this->message("正在建立数据, 当前: $start...", 1, $this->url("plugin-setting-dir-$dir-fid-$fid-start-$start.htm"));
	} else {
		$this->message('恭喜，建立数据成功。', 1, $this->url("plugin-setting-dir-$dir.htm"));
	}
}

?>