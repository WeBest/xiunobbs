<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class bbs_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['bbs'] = ' class="checked"';
		$this->_title[] = $this->conf['seo_title'] ? $this->conf['seo_title'] : $this->conf['app_name'];
		$this->_seo_keywords = $this->conf['seo_keywords'];
		$this->_seo_description = $this->conf['seo_description'];
	}
	
	public function on_index() {
		
		// hook index_bbs_before.php
		
		// 按照板块调用数据
		$forumarr = $this->conf['forumarr'];
		$threadlists = $this->runtime->get('threadlists');
		if(empty($threadlists)) {
			foreach($forumarr as $fid=>$name) {
				if(!empty($forumarr[$fid])) {
					$access = $this->forum_access->read($fid, $this->_user['groupid']);
					if(!empty($access) && !$access['allowread']) {
						unset($forumarr[$fid]);
						continue;
					}
				}
				$threadlist = $this->thread->get_threadlist_by_fid($fid, 0, 0, 2, 0);
				foreach($threadlist as &$thread) {
					$thread['dateline_fmt'] = misc::humandate($thread['dateline']);
					$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 18);
				}
				$threadlists[$fid] = $threadlist;
			}
			$this->runtime->set('threadlists', $threadlists, 60); // todo:一分钟的缓存时间！这里可以根据负载进行调节。
		}
		$this->view->assign('forumarr', $forumarr);
		$this->view->assign('threadlists', $threadlists);
		
		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('toplist', $toplist);
		$this->view->assign('click_server', $click_server);
		$this->view->assign('pages', $pages);
		
		$forumlist = $this->forum->get_list();
		foreach ($forumlist as &$forum) {
			$this->forum->format($forum);		
		}		
		$this->view->assign('forumlist', $forumlist);
		
		// hook index_bbs_after.php
		
		$this->view->display('bbs_index.htm');
	}
	
	private function get_toplist($forum = array()) {
		$fidtids = array();
		// 3 级置顶
		$fidtids = $this->get_fidtids($this->conf['toptids']);
		
		// 1 级置顶
		if($forum) {
			$fidtids += $this->get_fidtids($forum['toptids']);
		}
		
		$toplist = $this->thread->mget($fidtids);
		return $toplist;
	}
	
	private function get_fidtids($s) {
		$fidtids = array();
		if($s) {
			$fidtidlist = explode(' ', trim($s));
			foreach($fidtidlist as $fidtid) {
				if(empty($fidtid)) continue;
				$arr = explode('-', $fidtid);
				list($fid, $tid) = $arr;
				$fidtids["$fid-$tid"] = array($fid, $tid);
			}
		}
		return $fidtids;
	}
	
}
?>