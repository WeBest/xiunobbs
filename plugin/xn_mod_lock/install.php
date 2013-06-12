<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 检查版本
$s = file_get_contents(BBS_PATH.'control/common_control.class.php');
if(strpos($s, 'common_control_check_user_access_actiontext_after.php') === FALSE) {
	$this->message('请打完 XiunoBBS 2.0.3 2013/6/11 后补丁再进行安装。');
}

// 改文件会被 include 执行。
if($this->conf['db']['type'] != 'mongodb') {
	$db = $this->user->db;	// 与 user model 同一台 db
	$tablepre = $db->tablepre;
	try {$db->query("ALTER TABLE {$tablepre}group ADD COLUMN allowlock tinyint(3) NOT NULL default '0'");} catch(Exception $e) {}
	try {$db->query("ALTER TABLE {$tablepre}group ADD COLUMN allowupdown tinyint(3) NOT NULL default '0'");} catch(Exception $e) {}
	
	try {$db->query("UPDATE {$tablepre}group SET allowlock=1, allowupdown=1 WHERE groupid>0 AND groupid<=5");} catch(Exception $e) {}
}

?>