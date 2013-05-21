<?php

$error = $input = array();

$start = intval(core::gpc('start'));
$fid = intval(core::gpc('fid'));

if(!$this->form_submit() && empty($start)) {
	
	$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
	$this->view->assign('forumoptions', $forumoptions);
	
	$this->view->assign('dir', $dir);
	$this->view->display('xn_clear_rubbish.htm');
} else {
	
	if(empty($start)) {
		// 锁住
		$this->runtime->xset('site_runlevel', 4, 'runtime');
		$this->kv->xset('site_runlevel', 4, 'conf');
	}
	
	$limit = DEBUG ? 20 : 200;
	$arrlist = $this->thread->index_fetch(array('fid'=>$fid, 'tid'=>array('>'=>$start)), array('tid'=>1), 0, $limit);
	if(!empty($arrlist)) {
		$user_digests = $user_threads = $forum_threads = $forum_digests = array();
		foreach($arrlist as $arr) {
			if($thread['digest'] > 0) {
				$this->thread_digest->create(array('fid'=>$arr['fid'], 'tid'=>$arr['tid'], 'digest'=>$arr['digest']));
			}
			if($count_user) {
				!isset($user_threads[$arr['uid']]) && $user_threads[$arr['uid']] = 0;
				$user_threads[$arr['uid']]++;
				if($arr['digest']) {
					!isset($user_digests[$arr['uid']]) && $user_digests[$arr['uid']] = 0;
					$user_digests[$arr['uid']]++;
				}
			}
			if($count_forum) {
				!isset($forum_threads[$arr['fid']]) && $forum_threads[$arr['fid']] = 0;
				$forum_threads[$arr['fid']]++;
				if($arr['digest']) {
					!isset($forum_digests[$arr['fid']]) && $forum_digests[$arr['fid']] = 0;
					$forum_digests[$arr['fid']]++;
				}
			}
			if($count_threadtype) {
				if($arr['typeid1'] || $arr['typeid2'] || $arr['typeid3'] || $arr['typeid4']) {
					$this->thread_type_data->xcreate($arr['fid'], $arr['tid'], $arr['typeid1'], $arr['typeid2'], $arr['typeid3'], $arr['typeid4']);
				}
			}
		}
		$start = $arr['tid'];
		$this->message("正在重建统计数, 当前: $start...", 1, $this->url("plugin-setting-dir-$dir-count_user-$count_user-count_forum-$count_forum-count_threadtype-$count_threadtype-start-$start.htm"));
	} else {
		// 锁住
		$this->runtime->xset('site_runlevel', 0, 'runtime');
		$this->kv->xset('site_runlevel', 0, 'conf');
		try { $this->thread->index_drop(array('tid'=>1)); } catch (Exception $e) {}
		$this->message('恭喜，修改成功。', 1, $this->url("plugin-setting-dir-$dir.htm"));
	}
}

?>