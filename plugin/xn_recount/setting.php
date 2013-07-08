<?php

$error = $input = array();

$start = intval(core::gpc('start'));

if(!$this->form_submit() && empty($start)) {
	$this->view->assign('dir', $dir);
	$this->view->display('plugin_xn_recount.htm');
} else {
	
	$count_user = core::gpc('count_user', 'R');
	$count_forum = core::gpc('count_forum', 'R');
	$count_threadtype = core::gpc('count_threadtype', 'R');
	
	if(empty($start)) {
		// 锁住
		$this->runtime->xset('site_runlevel', 4, 'runtime');
		$this->kv->xset('site_runlevel', 4, 'conf');
		// 创建索引
		try {$this->thread->index_create(array('tid'=>1)); } catch (Exception $e) {}
		if($count_user) {
			$this->user->index_update(array(), array('digests'=>0, 'threads'=>0));
		}
		if($count_forum) {
			$this->forum->index_update(array(), array('digests'=>0, 'threads'=>0));
		}
		if($count_threadtype) {
			$this->thread_type_count->truncate();
			$this->thread_type_data->truncate();
		}
		$this->thread_digest->truncate();
	}
	
	$limit = DEBUG ? 20 : 200;
	$arrlist = $this->thread->index_fetch(array('tid'=>array('>'=>$start)), array('tid'=>1), 0, $limit);
	if(!empty($arrlist)) {
		$user_digests = $user_threads = $forum_threads = $forum_digests = array();
		foreach($arrlist as $arr) {
			if($arr['digest'] > 0) {
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
		if($user_threads) {
			foreach($user_threads as $_uid=>$_count) {
				$user = $this->user->read($_uid);
				$user['threads'] += $_count;
				$this->user->update($user);
			}
		}
		if($user_digests) {
			foreach($user_digests as $_uid=>$_count) {
				$user = $this->user->read($_uid);
				$user['digests'] += $_count;
				$this->user->update($user);
			}
		}
		if($forum_threads) {
			foreach($forum_threads as $_fid=>$_count) {
				$forum = $this->forum->read($_fid);
				$forum['threads'] += $_count;
				$this->forum->update($forum);
			}
		}
		if($forum_digests) {
			foreach($forum_digests as $_fid=>$_count) {
				$forum = $this->forum->read($_fid);
				$forum['digests'] += $_count;
				$this->forum->update($forum);
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