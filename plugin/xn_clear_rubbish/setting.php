<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

$error = $input = array();

$start = intval(core::gpc('start', 'R'));
$fid = intval(core::gpc('fid', 'R'));
$keepcredits = intval(core::gpc('keepcredits', 'R'));
$startdate_html = str_replace('_', '-', core::gpc('startdate', 'R'));
$enddate_html = str_replace('_', '-', core::gpc('enddate', 'R'));
$keyword = urldecode(core::gpc('keyword', 'R'));
$username = urldecode(core::gpc('username', 'R'));

$startdate_url = str_replace('-', '_', $startdate_html);
$enddate_url = str_replace('-', '_', $enddate_html);
$startdate = strtotime($startdate_html);
$enddate = strtotime($enddate_html) + 86400;
$keyword_url = core::urlencode($keyword);
$username_url = core::urlencode($username);
if(!$this->form_submit() && empty($start)) {
	
	$input = array();
	$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
	$this->view->assign('forumoptions', $forumoptions);
	$input['keepcredits'] = form::get_radio_yes_no('keepcredits', $keepcredits);
	
	empty($startdate_html) && $startdate_html = date('Y-n-j', $_SERVER['time']);
	empty($enddate_html) && $enddate_html = date('Y-n-j', $_SERVER['time']);
	$this->view->assign('startdate_html', $startdate_html);
	$this->view->assign('enddate_html', $enddate_html);
	$this->view->assign('keyword_url', $keyword_url);
	$this->view->assign('username_url', $username_url);
	$this->view->assign('dir', $dir);
	$this->view->assign('input', $input);
	$this->view->display('xn_clear_rubbish.htm');
} else {
	
	//echo date('Y-n-j H:i', $startdate);
	//echo date('Y-n-j H:i', $enddate);
	if(empty($start)) {
		// 全站只读
		$this->runtime->xset('site_runlevel', 4, 'runtime');
		$this->kv->xset('site_runlevel', 4, 'conf');
	}
	
	$limit = DEBUG ? 1 : 200;
	$arrlist = $this->thread->index_fetch(array('fid'=>$fid, 'tid'=>array('>'=>$start)), array('tid'=>1), 0, $limit);
	if(!empty($arrlist)) {
		$thread_return = array();
		foreach($arrlist as $arr) {
			$fid = $arr['fid'];
			$tid = $arr['tid'];
			if($startdate && $arr['dateline'] <= $startdate) continue;
			if($enddate && $arr['dateline'] >= $enddate) continue;
			if($keyword && stripos($arr['subject'], $keyword) !== FALSE) continue;
			$return = $this->thread->xdelete($fid, $tid, FALSE);
			$this->thread->xdelete_merge_return($thread_return, $return);
		}
		
		$this->thread->xdelete_update($thread_return, $keepcredits);
		
		$start = $arr['tid'];
		$this->message("正在清理, 当前: $start...", 1, $this->url("plugin-setting-dir-$dir-fid-$fid-start-$start-keepcredits-$keepcredits-startdate-$startdate_url-enddate-$enddate_url-keyword-$keyword_url-username-$username_url.htm"));
	} else {
		// 锁住
		$this->runtime->xset('site_runlevel', 0, 'runtime');
		$this->kv->xset('site_runlevel', 0, 'conf');
		$this->message('恭喜，清理成功。', 1, $this->url("plugin-setting-dir-$dir.htm"));
	}
}

?>