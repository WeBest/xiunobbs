<?php

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

$db = $this->user->db;	// 与 user model 同一台 db

$db->table_drop('shop_cate');
$db->table_create('shop_cate', array(
	array('cateid', 'int(11)'), 
	array('name', 'char(32)'), 
	array('goods', 'int(11)'), 
	array('rank', 'int(11)'), 
));
$db->index_create('shop_cate', array('cateid'=>1));

$db->table_drop('shop_good');
$db->table_create('shop_good', array(
	array('goodid', 'int(11)'), 
	array('cateid', 'int(11)'), 
	array('name', 'char(64)'), 
	array('cover', 'char(64)'), 
	array('price', 'int(11)'), 
	array('dateline', 'int(11)'), 
	array('orders', 'int(11)'), 
	array('replies', 'int(11)'), 
	array('views', 'int(11)'), 
	array('rank', 'int(11)'), 
));
$db->index_create('shop_good', array('goodid'=>1));
$db->index_create('shop_good', array('cateid'=>1, 'rank'=>1));

$db->table_drop('shop_order');
$db->table_create('shop_order', array(
	array('orderid', 'int(11)'), 
	array('goodid', 'int(11)'), 
	array('uid', 'int(11)'), 
	array('dateline', 'int(11)'), 
	array('year', 'int(11)'), 
	array('month', 'int(11)'), 
	array('day', 'int(11)'), 
	array('status', 'int(11)'),  // "0":"等待支付", "1":"已支付，等待发货", "2":"已发货，等待收货", "3":"已收货，交易完毕", "4":"无效订单"
	
	array('alipay_email', 'char(60)'), 
	array('alipay_orderid', 'char(60)'), 
	array('alipay_fee', 'int(11)'), 
	array('alipay_receive_name', 'char(10)'), 
	array('alipay_receive_phone', 'char(20)'), 
	array('alipay_receive_mobile', 'char(10)'), 
));
$db->index_create('shop_order', array('orderid'=>1));
$db->index_create('shop_order', array('year'=>1, 'month'=>1, 'day'=>1));

$db->table_drop('shop_reply');
$db->table_create('shop_reply', array(
	array('replyid', 'int(11)'), 
	array('goodid', 'int(11)'), 
	array('uid', 'int(11)'), 
	array('dateline', 'int(11)'), 
	array('message', 'text'), 
));
$db->index_create('shop_reply', array('replyid'=>1));
$db->index_create('shop_reply', array('goodid'=>1));

// 节省一个表，按照 id 存放。
// 附件存放于 attach_shop，按照天存储， upload/attach_shop/000/000/1_1234.jpg, upload/attach_shop/000/000/1_1235.jpg, 
?>