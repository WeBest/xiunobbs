<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class cms_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_title[] = $this->conf['seo_title'] ? $this->conf['seo_title'] : $this->conf['app_name'];
		$this->_seo_keywords = $this->conf['seo_keywords'];
		$this->_seo_description = $this->conf['seo_description'];
			
		$this->cms_channel = core::model($this->conf, 'cms_channel', array('channelid'), 'channelid');
		$this->cms_cate = core::model($this->conf, 'cms_cate', array('channelid', 'cateid'));
		$this->cms_article = core::model($this->conf, 'cms_article', array('articleid'), 'articleid');
	}
	
	public function on_channel() {
		
		$channelid = intval(core::gpc('channelid'));
		$cateid = intval(core::gpc('cateid'));
		$articleid = intval(core::gpc('articleid'));
		$channel = $this->cms_channel->read($channelid);
		empty($channel) && $this->message('频道不存在！');
		$catelist = $articlelist = array();
		
		if($channel['layout'] == 0) {
			$articleid = $channelid;
			$article = $this->cms_article->read($articleid);
			empty($article) && $this->message('正在建设中...');
		} elseif($channel['layout'] > 0) {
			$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 20);
			misc::arrlist_multisort($catelist, 'rank', TRUE);
			if(empty($cateid) && !empty($catelist)) {
				$first = array_shift($catelist);
				$cateid = $first['cateid'];
				array_unshift($catelist, $first);
			}
			$cate = $this->cms_cate->read($channelid, $cateid);
			empty($cate) && $this->message('文章分类不存在！');
			if($channel['layout'] == 1) {
				$articleid = $channelid * 20 + $cateid;
				$article = $this->cms_article->read($articleid);
				//var_dump($articleid);echo 123;
				empty($article) && $this->message('正在建设中...');
			} elseif($channel['layout'] == 2) {
				if($articleid) {
					$article = $this->cms_article->read($articleid);
					empty($article) && $this->message('正在建设中...');
					$article['views']++;
					$this->cms_article->update($article);
				} else {
					$article = array();
					$page = misc::page();
					$pagesize = 20;
					$pages = misc::pages("?cms-channel-channelid-$channelid.htm", $cate['articles'], $page, $pagesize);
					$start = ($page - 1) * $pagesize;
					$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid, 'cateid'=>$cateid), array('rank'=>1), $start, $pagesize);
				}
			}
		}
		!empty($article) && $article['dateline_fmt'] = date('Y-n-j', $article['dateline']);
		$this->_checked['cate_'.$cateid] = ' class="checked"';
		$this->_checked['channelid_'.$channelid] = ' class="checked"';
		$this->view->assign('page', $page);
		$this->view->assign('pages', $pages);
		$this->view->assign('channel', $channel);
		$this->view->assign('channelid', $channelid);
		$this->view->assign('cateid', $cateid);
		$this->view->assign('articleid', $articleid);
		$this->view->assign('article', $article);
		$this->view->assign('catelist', $catelist);
		$this->view->assign('articlelist', $articlelist);
		$this->view->display('cms_channel.htm');
	}
	
	/*public function on_article() {
		$articleid = intval(core::gpc('articleid'));
		$this->view->display('cms_article.htm');
	}*/
	
}
?>