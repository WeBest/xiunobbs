<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 改文件会被 include 执行。

$db = $this->user->db;	// 与 user model 同一台 db

$db->table_drop('cms_channel');
$db->table_create('cms_channel', array(
	array('channelid', 'int(11)'), 
	array('rank', 'int(11)'), 
	array('layout', 'int(11)'), 
	array('name', 'char(32)'), 
));
$db->index_create('cms_channel', array('channelid'=>1));

$db->table_drop('cms_cate');
$db->table_create('cms_cate', array(
	array('channelid', 'int(11)'), 
	array('cateid', 'int(11)'), 
	array('name', 'char(32)'),
	array('rank', 'int(11)'), 
	array('articles', 'int(11)'), 
));
$db->index_create('cms_cate', array('channelid'=>1, 'cateid'=>1));

$db->table_drop('cms_article');
$db->table_create('cms_article', array(
	array('channelid', 'int(11)'), 
	array('cateid', 'int(11)'), 
	array('articleid', 'int(11)'), 
	array('subject', 'varchar(255)'), 
	array('message', 'longtext'), 
	array('username', 'char(16)'), 
	array('dateline', 'int(11)'), 
	array('rank', 'int(11)'), 
	array('views', 'int(11)'),
));
$db->index_create('cms_article', array('channelid'=>1, 'cateid'=>1, 'rank'=>1));
$db->index_create('cms_article', array('articleid'=>1));

// 预留400篇文章, 20 * 20
$db->maxid('cms_article-articleid', 400);

?>