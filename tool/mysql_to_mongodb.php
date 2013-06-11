<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用将 mysql 数据转换到 Mongodb

/*
	流程：
		1. 将此文件拷贝到网站根目录
		2. 配置好 conf/conf.php 中 mysql, mongodb 参数
		3. 命令行下访问：/usr/local/php/bin/php /data/wwwroot/domain.com/mysql_to_mongodb.php
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

$db = new db_mysql($conf['db']['mysql']);
$mogo = new db_mongodb($conf['db']['mongodb']);

$tablelist = get_tables($db); // array('tablename'=>'', 'primarykey'=>'', 'count'=>'')
foreach($tablelist as $table) {
	echo "Table: [$table[tablename]] Rows:$table[count] \n";
	$count = $table['count'];
	$start = 0;
	$limit = DEBUG ? 1 : 100;
	while($start < $count) {
		$arrlist = $db->index_fetch($table['tablename'], $table['primarykey'], array(), array(), $start, $limit);
		foreach($arrlist as $arr) {
			echo '.';
			$key = get_key($table['tablename'], $table['primarykey'], $arr);
			$mongo->set($key, $arr);
		}
		$start += $limit;
	}
}

function get_key($tablename, $primarykey, $arr) {
	$s = $tablename;
	foreach($primarykey as $v) {
		$s .= "-$v-".$arr[$v];
	}
	return $s;
}

function get_tables($db) {
	$tablelist = $db->fetch_all("SHOW TABLES");
	/* 返回的格式：
		array(
			[0] => array('Table_in_test'=>'bbs_user'),
			[1] => array('Table_in_test'=>'bbs_thread'),
		)
	*/
	$return = array();
	foreach($tablelist as $table) {
		$table = array_values($table);
		$table = $table[0];
		$arr = $db->fetch_first("SHOW CREATE TABLE $table");
		$s = $arr['Create Table'];
		$return[$table] = parse_table($s, $db);
	}
	return $return;
}


function parse_table($s, $db) {
	$return = array();
	$s = str_replace('`', '', $s);
	preg_match('#CREATE TABLE (\w+)#is', $s, $m);
	if(isset($m[1])) {
		$cols = array();
		$n = strpos($m[1], '_');
		$tablename = substr($m[1], $n + 1);
		$tablepre = substr($m[1], 0, $n + 1);
		
		$rows = explode("\n", $s);
		$n = count($rows);
		
		if(stripos($rows[0], 'CREATE') === FALSE) {
			throw new Exception("[$tablename] 没有 CREATE");
		} else {
			unset($rows[0]);
		}
		
		$keyname = '';
		
		// 检测最后一行
		if(stripos($rows[$n - 1], 'ENGINE=') === FALSE) {
			throw new Exception("[$tablename 没有 ENGINE= 关键词");
		} else {
			unset($rows[$n - 1]);
		}
		$return['primarykey'] = parse_table_get_primarykey($rows, $tablename);
		$return['tablename'] = $tablename;
		$return['count'] = $db->index_count($tablename);
		
		
	}
	return $return;
}

function parse_table_get_primarykey(&$rows, $tablename) {
	// 检测 KEY, PRIMARY KEY
	$find = FALSE;
	foreach($rows as $k=>$v) {
		preg_match('#PRIMARY KEY\s*\(([^)]+)\)#is', $v, $_m);
		if(isset($_m[1])) {
			$find = TRUE;
			unset($rows[$k]);
			break;
		}
	}
	if(!$find) {
		throw new Exception("[$tablename] 没有 PRIMARY KEY({$tablename}id)");
	}
	$keyname = explode(',', str_replace(' ', '', $_m[1]));
	return $keyname;
}

?>