<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

if(!DEBUG) {
	// 改文件会被 include 执行。
	$db = $this->user->db;
	$db->table_drop('cms_channel');
	$db->table_drop('cms_cate');
	$db->table_drop('cms_article');
}
?>