<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用将 mysql 数据转换到 Mongodb
/*
	流程：
		1. 上传 reset_admin.php 到网站根目录
		3. 访问 http://www.domain.com/reset_admin.php
		5. 删除文件 reset_admin.php
*/

@set_time_limit(0);

define('DEBUG', 0);

define('BBS_PATH', str_replace('\\', '/', dirname(__FILE__)).'/');

// 加载应用的配置文件，唯一的全局变量 $conf
if(!($conf = include BBS_PATH.'conf/conf.php')) {
	exit('配置文件不存在，请将此文件放置于根目录。');
}
define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';

core::init();
core::ob_start();

$mysql = new db_mysql($conf['db']['mysql']);
$mogo = new db_mysql($conf['db']['mongodb']);

$tables = get_tables();
foreach($tables as $table) {
	// 获取id, 插入到数据库，按照正序
}
?>