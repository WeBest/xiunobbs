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
	}
	
	// 考虑默认过期时间
	public function on_index() {
			
		$error = $input = array();
		
		$this->cms_channel = core::model($this->conf, 'cms_channel', array('channelid'), 'channelid');
		$this->cms_cate = core::model($this->conf, 'cms_cate', array('channelid', 'cateid'));
		$this->cms_article = core::model($this->conf, 'cms_article', array('channelid', 'cateid', 'articleid'), 'articleid');
		
		$channelid = intval(core::gpc('channelid'));
		
		if(!$this->form_submit()) {
			
			$channellist = $this->cms_channel->index_fetch(array(), array(), 0, 20);
			if(count($channellist) > 0) {
				$channel = array_shift($channellist);
				$channelid = $channel['channel'];
			} else {
				$channel = array();
				$channelid = 0;
			}
			
			if($channel) {
				// 一篇文章
				if($channel['layout'] == 0) {
					$catelist = array();
					$article = $this->cms_article->index_fetch(array('channelid'=>$channelid, 'cateid'=>0), array(), 0, 1);
				// 几篇文章
				} elseif($channel['layout'] == 1) {
					$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 1);
					$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid));
				// 文章列表，分页
				} elseif($channel['layout'] == 2) {
					$page = misc::page();
					$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 20);
					$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid));
					
				}
			} else {
				$catelist = array();
				$articlelist = array();
			}
			
			// layout
			$layoutradios = form::get_radio('layout', array(0=>'一篇文章', 1=>'多篇文章', 2=>'分类+文章列表'), $channel['layout']);
			
			$this->view->display('xn_cms_admin_setting.htm');
		} else {
			
			$enable = core::gpc('enable', 'R');
			$meta = core::gpc('meta', 'R');
			$appid = core::gpc('appid', 'R');
			$appkey = core::gpc('appkey', 'R');
			
			if($meta) {
				if(!preg_match('#<meta[^<>]+/>#is', $meta)) {
					$this->message('meta标签格式不正确！');
				}
				$file = BBS_PATH.'plugin/xn_qq_login/header_css_before.htm';
				if(!file_put_contents($file, $meta)) {
					$this->message('写入文件 plugin/xn_qq_login/header_css_before.htm 失败，请检查文件是否可写<br />或者手工编辑此文件内容为：'.htmlspecialchars($meta));
				}
				// 删除 tmp 下的缓存文件
				misc::rmdir($this->conf['tmp_path'], 1);
			}
			
			$this->kv->set('qqlogin', array('enable'=>$enable, 'meta'=>$meta, 'appid'=>$appid, 'appkey'=>$appkey));
			$this->kv->xset('qqlogin_enable', $enable);
			$this->runtime->xset('qqlogin_enable', $enable);
			
			// 如果是 mysql 新建表
			
			$this->message('设置成功！', 1, $this->url('plugin-setting-dir-xn_qq_login.htm'));
			
		}
	}
}

?>