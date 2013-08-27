<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
		
	$db->table_drop('thread_blog');
	$db->table_create('thread_blog', array(
		array('fid', 'int(11)'), 
		array('tid', 'int(11)'), 
		array('coverimg', 'char(64)'), 
		array('brief', 'char(200)'), 
	));
	$db->index_create('thread_blog', array('fid'=>1, 'tid'=>1));
	
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