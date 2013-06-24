<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	
	try {
	$db->query("CREATE TABLE {$tablepre}thread_blog(
  fid int(10) NOT NULL default '0',	
  tid int(10) NOT NULL default '0',
  coverimg char(64) NOT NULL default '',
  brief char(200) NOT NULL default '',
  PRIMARY KEY (fid, tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
	} catch (Exception $e) {
		//echo $e->getMessage();
	}
	
	// 添加默认数据
	$threadlist = $this->thread->get_newlist(0, 1000);
	if(!empty($threadlist)) {
		foreach($threadlist as $k => $v) {
			$post = $db->get("post-fid-$v[fid]-pid-$v[firstpid]");
			$message = htmlspecialchars(utf8::substr(strip_tags($post['message']), 0, 140));
			$thread_blog = array('fid'=>$v['fid'], 'tid'=>$v['tid'], 'coverimg'=>'', 'brief'=>$message);
			$db->set("thread_blog-fid-$v[fid]-tid-$v[tid]", $thread_blog);
		}
	}
}

?>