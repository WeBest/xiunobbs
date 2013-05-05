<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来升级测试版本的 Xiuno BBS 2.0.* 到 Xiuno BBS 2.0.2
/*
	流程：
		1. 覆盖 control, model, view, admin, xiunophp 这五个目录
		2. 将 upgrade/xn201_to_xn202.php 放置于根目录
		3. 访问 http://www.domain.com/xn201_to_xn202.php
		4. 清空 tmp
		5. 删除文件 xn201_to_xn202.php
*/

@set_time_limit(0);

define('DEBUG', 2);

define('BBS_PATH', str_replace('\\', '/', dirname(__FILE__)).'/');

// 加载应用的配置文件，唯一的全局变量 $conf
if(!($conf = include BBS_PATH.'conf/conf.php')) {
	message('配置文件不存在。');
}
define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
define('FRAMEWORK_LOG_PATH', $conf['log_path']);
include FRAMEWORK_PATH.'core.php';

if(IN_SAE) {
	message('不支持SAE环境。');
}

core::init();
core::ob_start();
$step = core::gpc('step');
empty($step) && $step = 'complete';
$start = intval(core::gpc('start'));

// 升级配置文件
if($step == 'complete') {
	complete();
}

function complete() {
	global $conf;
	
	// 修改配置文件
	$conffile = BBS_PATH.'conf/conf.php';
	$s = file_get_contents($conffile);
	if(strpos($s, "'thread_new'=>array('thread_new'") === FALSE) {
		$s = str_replace("'thread_views'=>array('thread_views', 'tid', 'tid')", "'thread_views'=>array('thread_views', 'tid', 'tid'),\n\t\t'thread_new'=>array('thread_new', 'tid')", $s);
		$s = str_replace("'version' => '2.0.0 Release'", "'version' => '2.0.2'", $s);
		$s = str_replace("'version' => '2.0.1'", "'version' => '2.0.2'", $s);
		$s = str_replace("'version' => '2.0.2'", "'version' => '2.0.3'", $s);
		file_put_contents($conffile, $s);
	}
	
	$mconf = new xn_conf($configfile);
	$mconf->set('plugin_on', 1);
	$mconf->set('plugin_disable', 0);
	$mconf->save();
	
	message('升级完毕。', './');
}

function message($s, $url = '', $timeout = 2) {
	DEBUG && $timeout = 1000;
	
	$s = $url ? "<h2>$s</h2><p><a href=\"$url\">页面将在<b>$timeout</b>秒后自动跳转，点击这里手工跳转。</a></p>
		<script>
			setTimeout(function() {
				window.location=\"$url\";
				setInterval(function() {
					window.location=\"$url\";
				}, 30000);
			}, ".($timeout * 1000).");
		</script>
	" : "<h2>$s</h2>";
	echo '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Xiuno BBS 2.0.* - Xiuno BBS 2.0.2 升级程序 </title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="view/common.css" />
	</head>
	<body>
	<div id="header" style="overflow: hidden;">
		<h3 style="color: #FFFFFF;line-height: 26px;margin-left: 16px;">Xiuno BBS 2.0.* - Xiuno BBS 2.0.2  升级程序</h3>
		<p style="color: #BBBBBB;margin-left: 16px;">本程序用来升级Xiuno BBS 2.0.1。</p>
	</div>
	<div id="body" style="padding: 16px;">
		'.$s.'
	</div>
	<div id="footer"> Powered by Xiuno (c) 2010 </div>
	<div style="color: #888888;">'.(DEBUG ? nl2br(print_r($_SERVER['sqls'], 1)) : '').'</div>
	</body>
	</html>';
	exit;
}


?>