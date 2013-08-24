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
	array('message', 'text'), 
	array('cover', 'char(64)'), 
	array('price', 'int(11)'), 
	array('dateline', 'int(11)'), 
	array('stocks', 'int(11)'), // 库存数量
	array('orders', 'int(11)'), 
	array('replies', 'int(11)'), 
	array('views', 'int(11)'), 
	array('rank', 'int(11)'), 
	array('unit_name', 'char(16)'), // 单位名称：件，年，斤
));
$db->index_create('shop_good', array('goodid'=>1));
$db->index_create('shop_good', array('cateid'=>1, 'rank'=>1));

$db->table_drop('shop_image');
$db->table_create('shop_image', array(
	array('imageid', 'int(11)'), 
	array('goodid', 'int(11)'), 
	array('seq', 'int(11)'), 	// 图片的序列，第几张图片，默认为5张，seq 为0的。
	array('fileurl', 'char(64)'), // 000/001/123_24ekjlkfs.jpg
	array('width', 'int(11)'), 
	array('height', 'int(11)'), 
));
$db->index_create('shop_image', array('imageid'=>1));
$db->index_create('shop_image', array('goodid'=>1, 'seq'=>1));

$db->table_drop('shop_order');
$db->table_create('shop_order', array(
	array('orderid', 'int(11)'), 
	array('uid', 'int(11)'), 
	array('dateline', 'int(11)'), 
	array('price', 'int(11)'),  // 应支付的金额，json_good 计算的总金额，管理员应该可以修改此值。totalprice
	array('year', 'int(11)'), 
	array('month', 'int(11)'), 
	array('day', 'int(11)'), 
	array('status', 'int(11)'),  // "0":"等待支付", "1":"已支付，等待发货", "2":"已发货，等待收货", "3":"已收货，交易完毕", "4":"无效订单"
	
	array('json_amount', 'char(255)'), // json 格式存储的数据，格式如： {123:1, 124:2},大约一个订单能存储 10-20 个商品，保险起见，限制为10个商品。10 个提示购物车以满。
	
	array('recv_address', 'char(64)'),  // 收货地址
	array('recv_mobile', 'char(13)'),  // 收货人手机
	array('recv_name', 'char(16)'),  // 收货人姓名
	array('recv_comment', 'char(100)'),  // 收货人备注
	array('admin_comment', 'char(100)'),  // 管理员备注
	
	array('pay_type', 'int(11)'),     // 支付的方式：1: alipay, 2: ebank, 3: tenpay
	array('pay_orderid', 'char(32)'), // 外部订单号
	array('pay_amount', 'int(11)'),   // 支付金额
	array('pay_email', 'char(60)'),   // 支付的EMAIL（支付宝仅有）
	
	/*
	array('alipay_email', 'char(60)'), 
	array('alipay_orderid', 'char(60)'), 
	array('alipay_fee', 'int(11)'), 		// 支付宝支付的金额
	array('alipay_receive_name', 'char(10)'), 
	array('alipay_receive_phone', 'char(20)'), 
	array('alipay_receive_mobile', 'char(10)'), 
	
	array('ebank_orderid', 'char(32)'), 
	array('ebank_amount', 'int(11)'), 
	
	array('tenpay_transaction_id', 'char(32)'), 
	array('tenpay_total_fee', 'int(11)'), 
	*/
));

$db->index_create('shop_order', array('orderid'=>1));
$db->index_create('shop_order', array('uid'=>1, 'orderid'=>1));
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