<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class channel_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['article'] = ' class="checked"';
		$this->_title[] = $this->conf['seo_title'] ? $this->conf['seo_title'] : $this->conf['app_name'];
		$this->_seo_keywords = $this->conf['seo_keywords'];
		$this->_seo_description = $this->conf['seo_description'];
			
		$this->cms_channel = core::model($this->conf, 'cms_channel', array('channelid'), 'channelid');
		$this->cms_cate = core::model($this->conf, 'cms_cate', array('channelid', 'cateid'));
		$this->cms_article = core::model($this->conf, 'cms_article', array('articleid'), 'articleid');
	}
	
	public function on_index() {
		$channelid = intval(core::gpc('channelid'));
		
		$channel = $this->cms_channel->read($channelid);
		if($channel['layout'] == 0) {
			$articleid = $channelid;
			$article = $this->cms_article->read($articleid);
			$this->view->assign('article', $article);
		} elseif($channel['layout'] == 1) {
			
		} elseif($channel['layout'] == 2) {
			
		}
		$this->view->assign('channel', $channel);
		$this->view->assign('channelid', $channelid);
		$this->view->display('channel_index.htm');
	}
	
}
?>