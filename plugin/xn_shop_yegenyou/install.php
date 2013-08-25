<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

$db = $this->user->db;	// 与 user model 同一台 db
$tablepre = $db->tablepre;

$db->query("ALTER TABLE {$tablepre}shop_good ADD fileurl char(64) NOT NULL default '';");

?>