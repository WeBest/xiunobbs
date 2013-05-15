<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	try {
	$db->query("CREATE TABLE {$tablepre}friendlink(
  linkid int(10) unsigned NOT NULL auto_increment ,
  type tinyint(1) NOT NULL default '0',	
  rank tinyint(1) unsigned NOT NULL default '0',
  sitename char(16) NOT NULL default '',
  url char(64) NOT NULL default '',
  logo char(64) NOT NULL default '',
  PRIMARY KEY (linkid),
  KEY type (type, rank)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
	} catch (Exception $e) {}
}

$dir1 = $this->conf['upload_path'].'friendlink/';
!is_dir($dir1) && mkdir($dir1, 0777);

?>