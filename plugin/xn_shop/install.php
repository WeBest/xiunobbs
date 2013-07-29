<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	
	// 商品
	$db->query("CREATE TABLE IF NOT EXISTS {$tablepre}shop_goods (
		  goodsid int(11) unsigned NOT NULL auto_increment,
		  name char(32) NOT NULL DEFAULT '',
		  price int(11) unsigned NOT NULL DEFAULT '0',
		  dateline int(11) unsigned NOT NULL DEFAULT '0',  #  上架时间
		  PRIMARY KEY(goodsid)
	);");
	
	// 订单
	$db->query("CREATE TABLE IF NOT EXISTS {$tablepre}shop_order (
		  orderid int(11) unsigned NOT NULL DEFAULT '0',
		  goodsid int(11) unsigned NOT NULL DEFAULT '0',
		  uid int(11) unsigned NOT NULL DEFAULT '0',
		  username char(16) NOT NULL DEFAULT '',
		  rank int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY(orderid)
	);");
	
	// 评论
	$db->query("CREATE TABLE IF NOT EXISTS {$tablepre}shop_reply (
		  goodsid int(11) unsigned NOT NULL DEFAULT '0',
		  replyid int(11) unsigned NOT NULL DEFAULT '0',
		  message longtext NOT NULL default '',
		  uid int(11) unsigned NOT NULL DEFAULT '0',
		  username char(16) NOT NULL DEFAULT '',
		  dateline int(11) unsigned NOT NULL DEFAULT '0',
		  rank int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY(replyid)
	);");
	
}

?>