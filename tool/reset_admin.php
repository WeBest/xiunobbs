<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来重设管理员密码
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

$muser = new user($conf);
$muser->update_password(1, '1');

echo '<h1>管理员(uid=1) 的密码已经重置为 1，请尽快登陆以后修改密码。</h1>';
?>