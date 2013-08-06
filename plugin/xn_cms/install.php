<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$db->table_create('cms_channel', array(
		array('channelid', 'int(11)'), 
		array('rank', 'int(11)'), 
		array('layout', 'int(11)'), 
		array('name', 'char(32)'), 
	));
	
	$db->table_create('cms_channel', array(
		array('channelid', 'int(11)'), 
		array('rank', 'int(11)'), 
		array('layout', 'int(11)'), 
		array('name', 'char(32)'), 
	));
	$db->index_create('cms_channel', array('channelid'=>1));
	//$db->maxid('cms_article-articleid', 100);
	//$cms_channel = core::model($this->conf, 'cms_channel', array('channelid'), 'channelid');
	//$cms_channel->create();
	//$db->set("cms_channel-channelid-1", array(''));
	
	$db->table_create('cms_cate', array(
		array('channelid', 'int(11)'), 
		array('cateid', 'int(11)'), 
		array('name', 'char(32)'),
		array('rank', 'int(11)'), 
	));
	$db->index_create('cms_cate', array('channelid'=>1, 'cateid'=>1));
	
	$db->table_create('cms_article', array(
		array('channelid', 'int(11)'), 
		array('cateid', 'int(11)'), 
		array('articleid', 'int(11)'), 
		array('name', 'char(32)'), 
		array('subject', 'varchar(255)'), 
		array('message', 'longtext'), 
		array('username', 'char(16)'), 
		array('dateline', 'int(11)'), 
		array('rank', 'int(11)'), 
		array('views', 'int(11)'),
	));
	$db->index_create('cms_article', array('channelid'=>1, 'cateid'=>1, 'articleid'=>1));
	
	// 预留100篇文章
	$db->maxid('cms_article-articleid', 100);
}

?>