<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	try {$db->query("ALTER TABLE {$tablepre}thread ADD COLUMN custom_url char(64) NOT NULL default '';");} catch (Exception $e) {}
	try {$db->query("ALTER TABLE {$tablepre}thread ADD KEY custom_url(custom_url);");} catch (Exception $e) {}
}

?>