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
		$layout = intval(core::gpc('layout'));
		// 写入 channel
		if(isset($_GET['layout'])) {
			$channel = $this->cms_channel->read($channelid);
			$channel['layout'] = $layout;
			$this->cms_channel->update($channel);
		}
		
		$error = $input = array();
		
		$channellist = $this->cms_channel->index_fetch(array(), array(), 0, 20);
		misc::arrlist_multisort($channellist, 'rank', TRUE);
		if(isset($_GET['channelid'])) {
			$channel = $this->cms_channel->read($channelid);
		} else {
			$channel = array_shift($channellist);
			array_unshift($channellist, $channel);
		}
		
		if($channel) {
			// 一篇文章
			if($channel['layout'] == 0) {
				$catelist = array();
				$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid, 'cateid'=>0), array(), 0, 1);
				$article = array_pop($articlelist);
			// 几篇文章
			} elseif($channel['layout'] == 1) {
				$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 1);
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
			$layout = 0;
			$catelist = array();
			$articlelist = array();
			$article = array();
		}
		
		$layoutradios = form::get_radio('layout', array(0=>'单页面', 1=>'多篇文章', 2=>'分类+文章列表'), $layout);
		
		$newchannelid = $this->cms_channel->maxid() + 1;
		//$this->cms_channel->maxid();
		
		$this->view->assign('newchannelid', $newchannelid);
		$this->view->assign('channel', $channel);
		$this->view->assign('channelid', $channelid);
		$this->view->assign('layout', $layout);
		$this->view->assign('layoutradios', $layoutradios);
		$this->view->assign('article', $article);
		$this->view->assign('channellist', $channellist);
		$this->view->assign('catelist', $catelist);
		$this->view->assign('articlelist', $articlelist);
		$this->view->display('xn_cms_admin_setting.htm');
	}
	
	public function on_updatearticle() {
		if($this->form_submit()) {
			$channelid = intval(core::gpc('channelid'));
			$cateid = intval(core::gpc('cateid'));
			$articleid = intval(core::gpc('articleid'));
			$message = core::gpc('message', 'P');
			
			$article = $this->cms_article->read($channelid, $cateid, $articleid);
			empty($article) && $this->message('文章不存在。', 0);
			$article['subject'] = '';
			$article['message'] = $message;
			$this->cms_article->update($article);
		} else {
			$this->message('没有提交数据。', 0);
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
			$this->cms_channel->maxid($channelid);
			$this->cms_channel->create(array('channelid'=>$channelid, 'name'=>$name));
		} else {
			$channel['name'] = $name;
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
}

?>