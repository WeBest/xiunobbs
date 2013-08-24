<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 清理资源！

$db = $this->user->db;	// 与 user model 同一台 db

$db->table_drop('shop_cate');
$db->index_drop('shop_cate', array('cateid'=>1));

$db->table_drop('shop_good');
$db->index_drop('shop_good', array('goodid'=>1));
$db->index_drop('shop_good', array('cateid'=>1, 'rank'=>1));

$db->table_drop('shop_order');
$db->index_drop('shop_order', array('orderid'=>1));
$db->index_drop('shop_order', array('year'=>1, 'month'=>1, 'day'=>1));

$db->table_drop('shop_reply');
$db->index_drop('shop_reply', array('replyid'=>1));
$db->index_drop('shop_reply', array('goodid'=>1));

$db->delete("kv-k-shop_setting");

misc::rmdir($conf['upload_path'].'attach_shop/');

?>