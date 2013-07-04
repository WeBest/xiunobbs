<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	try {$db->query("ALTER TABLE {$tablepre}thread ADD column brief char(200) NOT NULL DEFAULT ''");} catch (Exception $e) {}
	try {$db->query("ALTER TABLE {$tablepre}thread ADD column coverimg char(128) NOT NULL DEFAULT ''");} catch (Exception $e) {}
}

// 开始初始化数据
?>