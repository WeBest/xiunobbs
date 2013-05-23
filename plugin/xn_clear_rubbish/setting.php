<?php

$error = $input = array();

$start = intval(core::gpc('start'));
$fid = intval(core::gpc('fid'));
$keepcredits = intval(core::gpc('keepcredits'));

if(!$this->form_submit() && empty($start)) {
	
	$input = array();
	$forumoptions = $this->forum->get_options($this->_user['uid'], $this->_user['groupid'], $fid, $defaultfid);
	$this->view->assign('forumoptions', $forumoptions);
	$input['keepcredits'] = form::get_radio_yes_no('keepcredits', $keepcredits);
	
	$this->view->assign('dir', $dir);
	$this->view->assign('input', $input);
	$this->view->display('xn_clear_rubbish.htm');
} else {
	
	if(empty($start)) {
		// 全站只读
		$this->runtime->xset('site_runlevel', 4, 'runtime');
		$this->kv->xset('site_runlevel', 4, 'conf');
	}
	
	$limit = DEBUG ? 20 : 200;
	$arrlist = $this->thread->index_fetch(array('fid'=>$fid, 'tid'=>array('>'=>$start)), array('tid'=>1), 0, $limit);
	if(!empty($arrlist)) {
		$thread_return = array();
		foreach($arrlist as $arr) {
			$fid = $arr['fid'];
			$tid = $arr['tid'];
			$return = $this->thread->xdelete($fid, $tid, FALSE);
			$this->thread->xdelete_merge_return($thread_return, $return);
		}
		
		$this->thread->xdelete_update($thread_return, $keepcredits);
		
		$start = $arr['tid'];
		$this->message("正在清理, 当前: $start...", 1, $this->url("plugin-setting-dir-$dir-keepcredits-$keepcredits-start-$start.htm"));
	} else {
		// 锁住
		$this->runtime->xset('site_runlevel', 0, 'runtime');
		$this->kv->xset('site_runlevel', 0, 'conf');
		$this->message('恭喜，清理成功。', 1, $this->url("plugin-setting-dir-$dir.htm"));
	}
}

?>