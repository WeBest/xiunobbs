<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	$db->query("CREATE TABLE IF NOT EXISTS {$tablepre}cms_channel (
		  channelid int(11) unsigned NOT NULL auto_increment,
		  name char(32) NOT NULL DEFAULT '',
		  rank int(11) unsigned NOT NULL DEFAULT '0',
		  layout int(11) unsigned NOT NULL DEFAULT '0',  # 0: 一篇文章; 1: 多篇文章; 2: 分类+文章列表
		  PRIMARY KEY(channelid)
	);");
	
	// 每个频道下最多20个分类
	$db->query("CREATE TABLE IF NOT EXISTS {$tablepre}cms_cate (
		  channelid int(11) unsigned NOT NULL DEFAULT '0',
		  cateid int(11) unsigned NOT NULL DEFAULT '0',
		  name char(32) NOT NULL DEFAULT '',
		  rank int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY(channelid, cateid)
	);");
	
	$db->query("CREATE TABLE IF NOT EXISTS {$tablepre}cms_article (
		  channelid int(11) unsigned NOT NULL DEFAULT '0',
		  cateid int(11) unsigned NOT NULL DEFAULT '0', # 0: 表示一篇文章; >0: 表示
		  articleid int(11) unsigned NOT NULL auto_increment,
		  subject varchar(255) NOT NULL default '',
		  message longtext NOT NULL default '',
		  dateline int(11) unsigned NOT NULL DEFAULT '0',
		  username char(16) NOT NULL DEFAULT '',
		  views int(11) unsigned NOT NULL DEFAULT '0',
		  rank int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY(channelid, cateid, articleid)
	);");
	
}

?>