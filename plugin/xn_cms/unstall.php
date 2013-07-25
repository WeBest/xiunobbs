<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

if(!DEBUG) {
	// 改文件会被 include 执行。
	if($this->conf['db']['type'] != 'mongodb') {
		$db = $this->user->db;	// 与 user model 同一台 db
		$tablepre = $db->tablepre;
		$db->query("DROP TABLE IF EXISTS {$tablepre}cms_channel;");
		$db->query("DROP TABLE IF EXISTS {$tablepre}cms_cate;");
		$db->query("DROP TABLE IF EXISTS {$tablepre}cms_article;");
	}
}
?>