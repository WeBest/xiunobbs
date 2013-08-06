<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'admin/control/admin_control.class.php';

class cms_control extends admin_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->check_admin_group();
		
		$this->cms_channel = core::model($this->conf, 'cms_channel', array('channelid'), 'channelid');
		$this->cms_cate = core::model($this->conf, 'cms_cate', array('channelid', 'cateid'));
		$this->cms_article = core::model($this->conf, 'cms_article', array('channelid', 'cateid', 'articleid'), 'articleid');
	}
	
	// 考虑默认过期时间
	public function on_index() {
		
		$channelid = intval(core::gpc('channelid'));
		$cateid = intval(core::gpc('cateid'));
		$layout = intval(core::gpc('layout'));
		$channellist = $this->cms_channel->index_fetch(array(), array(), 0, 20);
		misc::arrlist_multisort($channellist, 'rank', TRUE);
		if(empty($channelid)) {
			$first = array_shift($channellist);
			$channelid = $first['channelid'];
			array_unshift($channellist, $first);
		}
		$channel = $this->cms_channel->read($channelid);
		
		// 写入 channel
		if(isset($_GET['layout']) && $channel) {
			$channel['layout'] = $layout;
			$this->cms_channel->update($channel);
		}
		
		$error = $input = array();
		
		$newchannelids = core::json_encode($this->get_newchannelids());
		$newcateids = core::json_encode($this->get_newcateids($channelid));
		$this->view->assign('newchannelids', $newchannelids);
		$this->view->assign('newcateids', $newcateids);
		
		if($channel) {
			// 一篇文章
			if($channel['layout'] == 0) {
				$catelist = array();
				$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid, 'cateid'=>0), array(), 0, 1);
				$article = array_pop($articlelist);
			// 几篇文章
			} elseif($channel['layout'] == 1) {
				$newcateid = $this->get_cate_max_rank($channelid);
				$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 20);
				// 默认 cateid
				if(empty($cateid) && !empty($catelist)) {
					$first = array_shift($catelist);
					$cateid = $first['cateid'];
					array_unshift($catelist, $first);
				}
				
				$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid));
				
			// 文章列表，分页
			} elseif($channel['layout'] == 2) {
				$cateid = intval(core::gpc('cateid'));
				$page = misc::page();
				$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 20);
				$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid, 'cateid'=>$cateid));
			}
			$layout = $channel['layout'];
		} else {
			if($channel['layout'] == 0) {
				$catelist = $articlelist = $article = array();
			} elseif($channel['layout'] == 1) {
				$newcateid = $cateid = 0;
				$catelist = $articlelist = array();
			} elseif($channel['layout'] == 2) {
				$cateid = $page = 0;
				$catelist = $articlelist = array();
			}
		}
		
		$layoutradios = form::get_radio('layout', array(0=>'单页面', 1=>'多篇文章', 2=>'分类+文章列表'), $layout);
		
		$newchannelid = $this->get_channel_max_rank();
		
		$this->view->assign('newchannelid', $newchannelid);
		$this->view->assign('channel', $channel);
		$this->view->assign('channelid', $channelid);
		$this->view->assign('cateid', $cateid);
		$this->view->assign('layout', $layout);
		$this->view->assign('layoutradios', $layoutradios);
		$this->view->assign('article', $article);
		$this->view->assign('channellist', $channellist);
		$this->view->assign('catelist', $catelist);
		$this->view->assign('articlelist', $articlelist);
		$this->view->display('xn_cms_admin_setting.htm');
	}
	
	//
	public function on_updatearticle() {
		if($this->form_submit()) {
			$channelid = intval(core::gpc('channelid'));
			$cateid = intval(core::gpc('cateid'));
			$articleid = intval(core::gpc('articleid'));
			$message = core::gpc('message', 'P');
			
			$channel = $this->cms_channel->read($channelid);
			empty($channel) && $this->message('频道不存在。', 0);
			
			if($channel['layout'] == 0) {
				$article = $this->cms_article->read($channelid, $cateid, $channelid);
				if(empty($article)) {
					$article = array('channelid'=>$channelid, 'cateid'=>0, 'articleid'=>$channelid, 'message'=>$message);
					$this->cms_article->create($article);
				} else {
					$article['message'] = $message;
					$this->cms_article->update($article);
				}
			} elseif($channel['layout'] > 0) {
				$article = $this->cms_article->read($channelid, $cateid, $articleid);
				empty($article) && $this->message('文章不存在。', 0);
				$article['message'] = $message;
				$this->cms_article->update($article);
			}
			$this->message('更新成功', 0);
			
		} else {
			$this->message('没有提交数据。', 1);
		}
	}
	
	public function on_deletearticle() {
		$channelid = intval(core::gpc('channelid'));
		$cateid = intval(core::gpc('cateid'));
		$articleid = intval(core::gpc('articleid'));
		
		$article = $this->cms_article->read($channelid, $cateid, $articleid);
		empty($article) && $this->message('文章不存在。', 0);
		$this->cms_article->delete($channelid, $cateid, $articleid);
		
		$this->message('删除成功。', 1);
	}
	
	// 修改 channel.name
	public function on_updatechannel() {
		$channelid = intval(core::gpc('channelid'));
		$name = core::gpc('name');
		
		// channelid
		$channel = $this->cms_channel->read($channelid);
		if(empty($channel)) {
			$this->get_newchannelid();
			$this->cms_channel->create(array('channelid'=>$channelid, 'name'=>$name));
		} else {
			$channel['name'] = $name;
			$channel['rank'] = $this->get_channel_max_rank() + 1;
			$this->cms_channel->update($channel);
		}
		
		$this->message('成功！', 1);
	}
	
	public function on_deletechannel() {
		$channelid = intval(core::gpc('channelid'));
		$this->cms_channel->delete($channelid);
		$this->message('成功！');
	}
	
	// 设置 rank
	public function on_rankchannel() {
		$data = urldecode(core::gpc('data'));
		$arr = misc::explode(':', '|', $data);
		$rank = 0;
		foreach($arr as $channelid=>$name) {
			$rank++;
			$channel = $this->cms_channel->read($channelid);
			if(empty($channel)) continue;
			$channel['rank'] = $rank;
			$this->cms_channel->update($channel);
		}
		$this->message('成功', 1);
	}
	
	// 修改 cate.name
	public function on_updatecate() {
		$channelid = intval(core::gpc('channelid'));
		$cateid = intval(core::gpc('cateid'));
		$name = core::gpc('name');
		
		// cateid
		$cate = $this->cms_cate->read($channelid, $cateid);
		if(empty($cate)) {
			$cateid = $this->get_newcateid($channelid);
			$this->cms_cate->create(array('channelid'=>$channelid, 'cateid'=>$cateid, 'name'=>$name));
		} else {
			$cate['name'] = $name;
			$cate['rank'] = $this->get_cate_max_rank($channelid) + 1;
			$this->cms_cate->update($cate);
		}
		
		$this->message('成功！', 1);
	}
	
	public function on_deletecate() {
		$channelid = intval(core::gpc('channelid'));
		$cateid = intval(core::gpc('cateid'));
		$this->cms_cate->delete($channelid, $cateid);
		$this->message('成功！');
	}
	
	// 设置 rank
	public function on_rankcate() {
		$channelid = intval(core::gpc('channelid'));
		$data = urldecode(core::gpc('data'));
		$arr = misc::explode(':', '|', $data);
		$rank = 0;
		foreach($arr as $cateid=>$name) {
			$rank++;
			$cate = $this->cms_cate->read($channelid, $cateid);
			if(empty($cate)) continue;
			$cate['rank'] = $rank;
			$this->cms_cate->update($cate);
		}
		$this->message('成功', 1);
	}
	
	private function get_newchannelid() {
		for($i=1; $i<20; $i++) {
			$channel = $this->cms_channel->read($i);
			if(empty($channel)) {
				return $i;
			}
		}
		return 0;
	}
	
	private function get_newcateid($channelid) {
		for($i=1; $i<20; $i++) {
			$cate = $this->cms_cate->read($channelid, $i);
			if(empty($cate)) {
				return $i;
			}
		}
		return 0;
	}
	
	private function get_newchannelids() {
		$arr = array();
		for($i=1; $i<20; $i++) {
			$channel = $this->cms_channel->read($i);
			if(empty($channel)) {
				$arr[] = $i;
			}
		}
		return $arr;
	}
	
	private function get_newcateids($channelid) {
		$arr = array();
		for($i=1; $i<20; $i++) {
			$cate = $this->cms_cate->read($channelid, $i);
			if(empty($cate)) {
				$arr[] = $i;
			}
		}
		return $arr;
	}
	
	private function get_channel_max_rank() {
		$maxrank = 0;
		for($i=1; $i<20; $i++) {
			$channel = $this->cms_channel->read($i);
			if(!empty($channel) && $channel['rank'] > $maxrank) {
				$maxrank = $channel['rank'];
			}
		}
		return $maxrank;
	}
	
	private function get_cate_max_rank($channelid) {
		$maxrank = 0;
		for($i=1; $i<20; $i++) {
			$cate = $this->cms_cate->read($channelid, $i);
			if(!empty($cate) && $cate['rank'] > $maxrank) {
				$maxrank = $cate['rank'];
			}
		}
		return $maxrank;
	}
}

?>