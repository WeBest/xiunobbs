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
		$this->cms_article = core::model($this->conf, 'cms_article', array('articleid'), 'articleid');
	}
	
	// 考虑默认过期时间
	public function on_index() {
		
		$channelid = intval(core::gpc('channelid'));
		$cateid = intval(core::gpc('cateid'));
		$layout = intval(core::gpc('layout'));
		$channellist = $this->cms_channel->index_fetch(array(), array(), 0, 20);
		misc::arrlist_multisort($channellist, 'rank', TRUE);
		if(empty($channelid) && $channellist) {
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
				$articleid = $channelid;
				$article = $this->cms_article->read($articleid);
			} elseif($channel['layout'] > 0) {
				
				$newcateid = $this->get_newcateid($channelid);
				$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 20);
				misc::arrlist_multisort($catelist, 'rank', TRUE);
				if(empty($cateid) && !empty($catelist)) {
					$first = array_shift($catelist);
					$cateid = $first['cateid'];
					array_unshift($catelist, $first);
				}
				$cate = $this->cms_cate->read($channelid, $cateid);
				
				if($channel['layout'] == 1) {
					$articleid = $channelid * 20 + $cateid;
					$article = $this->cms_article->read($articleid);
				} elseif($channel['layout'] == 2) {
					$article = array();
					$atticleid = $this->cms_article->maxid() + 1;
				}
				
				$pagesize = 20;
				$page = misc::page();
				$pages = misc::pages("?cms-index-channelid-$channelid.htm", $cate['articles'], $page, $pagesize);
				$start = ($page - 1) * $pagesize;
				$articlelist = $this->cms_article->index_fetch(array('channelid'=>$channelid, 'cateid'=>$cateid), array('rank'=>1), $start, $pagesize);
				$this->view->assign('pages', $pages);
			}
			$layout = $channel['layout'];
		} else {
			if($channel['layout'] == 0) {
				$catelist = $articlelist = $article = array();
			} elseif($channel['layout'] > 0) {
				$newcateid = $cateid = $page = 0;
				$article = $catelist = $articlelist = array();
			}
		}
		
		$layoutradios = form::get_radio('layout', array(0=>'单页面', 1=>'多篇文章', 2=>'分类+文章列表'), $layout);
		$newchannelid = $this->get_newchannelid();
		$this->view->assign('newchannelid', $newchannelid);
		$this->view->assign('newcateid', $newcateid);
		$this->view->assign('articleid', $articleid);
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
	
	public function on_createarticle() {
		$channelid = intval(core::gpc('channelid'));
		$cateid = intval(core::gpc('cateid'));
		if(!$this->form_submit()) {
			$this->view->assign('channelid', $channelid);
			$this->view->assign('cateid', $cateid);
			$this->view->display('xn_cms_admin_article_create_ajax.htm');
		} else {
			$subject = core::gpc('subject', 'P');
			$message = core::gpc('message', 'P');
			$article = array(
				'channelid'=>$channelid,
				'cateid'=>$cateid,
				'subject'=>$subject,
				'message'=>$message,
				'rank'=>100000,
				'username'=>'',
				'dateline'=>$_SERVER['time'],
				'views'=>0,
			);
			$articleid = $this->cms_article->create($article);
			$this->process_attach($articleid);
			$cate = $this->cms_cate->read($channelid, $cateid);
			$cate['articles']++;
			$this->cms_cate->update($cate);
			$this->message('提交成功！');
		}
	}
	
	// 删除无关联的垃圾附件！
	private function process_attach($articleid) {
		$article = $this->cms_article->read($articleid);
		$path = $this->conf['upload_path'].'attach_cms'.'/'.date('Ymd', $article['dateline']).'/';
		if(!is_dir($path)) return;
		$files = misc::scandir($path);
		foreach($files as $file) {
			$arr = explode('_', $file);
			if(!empty($arr[0]) && $arr[0] == $articleid) {
				if(strpos($article['message'], $file) === FALSE) {
					unlink($path.$file);
				}
			}
		}
	}
	
	// 编辑文章
	public function on_updatearticle() {
		$articleid = intval(core::gpc('articleid'));
		$article = $this->cms_article->read($articleid);
		empty($article) && $this->message('文章不存在！', 0);
		
		if(!$this->form_submit()) {
			$article['message_html'] = htmlspecialchars($article['message']);
			$this->view->assign('articleid', $articleid);
			$this->view->assign('article', $article);
			$this->view->display('xn_cms_admin_article_update_ajax.htm');
		} else {
			$message = core::gpc('message', 'P');
			$subject = core::gpc('subject', 'P');
			$article['subject'] = $subject;
			$article['message'] = $message;
			$this->cms_article->update($article);
			$this->process_attach($articleid);
			$this->message('更新成功' , 1);
		}
	}
	
	// 更新文章
	public function on_editarticle() {
		if($this->form_submit()) {
			$channelid = intval(core::gpc('channelid'));
			$cateid = intval(core::gpc('cateid'));
			$articleid = intval(core::gpc('articleid'));
			$message = core::gpc('message', 'P');
			
			$channel = $this->cms_channel->read($channelid);
			empty($channel) && $this->message('频道不存在。', 0);
			
			if($channel['layout'] == 0) {
				$article = $this->cms_article->read($channelid);
				if(empty($article)) {
					$article = array(
						'channelid'=>$channelid,
						'cateid'=>0,
						'articleid'=>$channelid,
						'subject'=>'',
						'message'=>$message,
						'rank'=>100000,
						'username'=>'',
						'dateline'=>$_SERVER['time'],
						'views'=>0
					);
					$this->cms_article->create($article);
				} else {
					$article['message'] = $message;
					$this->cms_article->update($article);
				}
			} elseif($channel['layout'] == 1) {
				$articleid = $channelid * 20 + $cateid;
				$article = $this->cms_article->read($articleid);
				if(empty($article)) {
					$article = array(
						'channelid'=>$channelid,
						'cateid'=>$cateid,
						'articleid'=>$articleid,
						'subject'=>'',
						'message'=>$message,
						'rank'=>100000,
						'username'=>'',
						'dateline'=>$_SERVER['time'],
						'views'=>0,
					);
					$articleid = $this->cms_article->create($article);
				} else {
					$article['message'] = $message;
					$this->cms_article->update($article);
				}
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
		
		$article = $this->cms_article->read($articleid);
		empty($article) && $this->message('文章不存在。', 0);
		$channel = $this->cms_channel->read($article['channelid']);
		$this->cms_article->delete($articleid);
		
		if($channel['layout'] == 2) {
			$cate = $this->cms_cate->read($article['channelid'], $article['cateid']);
			if($cate) {
				$cate['articles']--;
				$this->cms_cate->update($cate);
			}
		}
		
		$this->message('删除成功。', 1, "?cms-index-channelid-$channelid-cateid-$cateid.htm");
	}
	
	// 修改 channel.name
	public function on_updatechannel() {
		$channelid = intval(core::gpc('channelid'));
		$name = core::urldecode(core::gpc('name'));
		
		// channelid
		$channel = $this->cms_channel->read($channelid);
		if(empty($channel)) {
			$channelid = $this->get_newchannelid();
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
		// 遍历 cate
		$catelist = $this->cms_cate->index_fetch(array('channelid'=>$channelid), array(), 0, 20);
		foreach($catelist as $cate) {
			$this->delete_article_by_cateid($channelid, $cate['cateid']);
		}
		$this->message('成功！');
	}
	
	private function delete_article_by_cateid($channelid, $cateid) {
		return $this->cms_article->index_delete(array('channelid'=>$channelid, 'cateid'=>$cateid));
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
		$name = core::urldecode(core::gpc('name'));
		
		$channel = $this->cms_channel->read($channelid);
		empty($channel) && $this->message('频道不存在!', 0);
		// cateid
		$cate = $this->cms_cate->read($channelid, $cateid);
		if(empty($cate)) {
			$cateid = $this->get_newcateid($channelid);
			$this->cms_cate->create(array('channelid'=>$channelid, 'cateid'=>$cateid, 'name'=>$name, 'articles'=>0));
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
		$this->delete_article_by_cateid($channelid, $cateid);
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
	
	// 设置 rank
	public function on_rankarticle() {
		$rank = core::gpc('rank', 'P');
		foreach($rank as $articleid=>$rank) {
			$article = $this->cms_article->read($articleid);
			if(empty($article)) continue;
			$article['rank'] = $rank;
			$this->cms_article->update($article);
		}
		$this->message('更新排序成功', 1);
	}
	
	private function get_newchannelid() {
		for($i=1; $i<=20; $i++) {
			$channel = $this->cms_channel->read($i);
			if(empty($channel)) {
				return $i;
			}
		}
		return 0;
	}
	
	private function get_newcateid($channelid) {
		for($i=1; $i<=20; $i++) {
			$cate = $this->cms_cate->read($channelid, $i);
			if(empty($cate)) {
				return $i;
			}
		}
		return 0;
	}
	
	private function get_newchannelids() {
		$arr = array();
		for($i=1; $i<=20; $i++) {
			$channel = $this->cms_channel->read($i);
			if(empty($channel)) {
				$arr[] = $i;
			}
		}
		return $arr;
	}
	
	private function get_newcateids($channelid) {
		$arr = array();
		for($i=1; $i<=20; $i++) {
			$cate = $this->cms_cate->read($channelid, $i);
			if(empty($cate)) {
				$arr[] = $i;
			}
		}
		return $arr;
	}
	
	private function get_channel_max_rank() {
		$maxrank = 0;
		for($i=1; $i<=20; $i++) {
			$channel = $this->cms_channel->read($i);
			if(!empty($channel) && $channel['rank'] > $maxrank) {
				$maxrank = $channel['rank'];
			}
		}
		return $maxrank;
	}
	
	private function get_cate_max_rank($channelid) {
		$maxrank = 0;
		for($i=1; $i<=20; $i++) {
			$cate = $this->cms_cate->read($channelid, $i);
			if(!empty($cate) && $cate['rank'] > $maxrank) {
				$maxrank = $cate['rank'];
			}
		}
		return $maxrank;
	}
	
	// 上传图片
	public function on_uploadimage() {
		
		$atticleid = intval(core::gpc('articleid'));
		
		// 对付一些变态的 iis 环境， is_file() 无法检测无权限的目录。
		$tmpfile = FRAMEWORK_TMP_TMP_PATH.md5(rand(0, 1000000000).$_SERVER['time'].$_SERVER['ip']).'.tmp';
		$succeed = IN_SAE ? copy($_FILES['Filedata']['tmp_name'], $tmpfile) : move_uploaded_file($_FILES['Filedata']['tmp_name'], $tmpfile);
		if(!$succeed) {
			$this->message('移动临时文件错误，请检查临时目录的可写权限。', 0);
		}
		
		$file = $_FILES['Filedata'];
		$file['tmp_name'] = $tmpfile;
		core::htmlspecialchars($file['name']);
		$filetype = $this->attach->get_filetype($file['name']);
		
		$uploadpath = $this->conf['upload_path'].'attach_cms/';
		$uploadurl = $this->conf['upload_url'].'attach_cms/';
		
		!is_dir($uploadpath) && mkdir($uploadpath, 0777);
		
		if($filetype == 'image') {
			// 处理文件
			$imginfo = getimagesize($file['tmp_name']);
			
			// 按照天存储
			$day = date('Ymd', $_SERVER['time']);
			!is_dir($uploadpath.$day) && mkdir($uploadpath.$day, 0777);
			if($imginfo[2] == 1) {
				$fileurl = $day.'/'.$atticleid.'_'.rand(1, 99999999).'.gif';
				$thumbfile = $uploadpath.$fileurl;
				copy($file['tmp_name'], $thumbfile);
				$r['filesize'] = filesize($file['tmp_name']);
				$r['width'] = $imginfo[0];
				$r['height'] = $imginfo[1];
				$r['fileurl'] = $fileurl;
			} else {
				$destext = image::ext($file['name']);
				$fileurl = $day.'/'.$atticleid.'_'.rand(1, 99999999).'.'.$destext;
				$thumbfile = $uploadpath.$fileurl;
				image::thumb($file['tmp_name'], $thumbfile, 1600, 16000);
				$imginfo = getimagesize($thumbfile);
				$r['filesize'] = filesize($thumbfile);
				$r['width'] = $imginfo[0];
				$r['height'] = $imginfo[1];
				$r['fileurl'] = $fileurl;
			}
			
			is_file($file['tmp_name']) && unlink($file['tmp_name']);
			
			$this->message('<img src="'.$uploadurl.$r['fileurl'].'" width="'.$r['width'].'" height="'.$r['height'].'"/>');
			
		} else {
			// 按照天存储
			$day = date('Ymd', $_SERVER['time']);
			!is_dir($uploadpath.$day) && mkdir($uploadpath.$day, 0777);
			$ext = image::ext($file['name']);
			$ext = $this->attach->safe_ext('.'.$ext);
			$desturl = $day.'/'.$atticleid.'_'.rand(1, 99999999)."$ext";
			$destfile = $uploadpath.$desturl;
			copy($file['tmp_name'], $destfile);
			global $bbsconf;
			$this->message('<a href="'.$uploadurl.$desturl.'"><img src="'.$bbsconf['static_url'].'view/image/filetype/'.$filetype.'.gif" width="16" height="16" />'.$file['name'].'</a>');
		}
		
	}
}

?>