<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class index_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['bbs'] = ' class="checked"';
		$this->_title[] = $this->conf['seo_title'] ? $this->conf['seo_title'] : $this->conf['app_name'];
		$this->_seo_keywords = $this->conf['seo_keywords'];
		$this->_seo_description = $this->conf['seo_description'];
	}
	
	// 给插件预留个位置
	public function on_index() {

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
	
	array('alipay_email', 'char(60)'), 
	array('alipay_orderid', 'char(60)'), 
	array('alipay_fee', 'int(11)'), 		// 支付宝支付的金额
	array('alipay_receive_name', 'char(10)'), 
	array('alipay_receive_phone', 'char(20)'), 
	array('alipay_receive_mobile', 'char(10)'), 
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
		// hook index_index_before.php
		
		$this->on_bbs();
	}
	
	// 首页
	public function on_bbs() {
		$this->_checked['index'] = ' class="checked"';
		
		// hook index_bbs_before.php
		
		$pagesize = 30;
		$toplist = array(); // only top 3
		$readtids = '';
		$page = misc::page();
		$start = ($page -1 ) * $pagesize;
		$threadlist = $this->thread->get_newlist($start, $pagesize);
		foreach($threadlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			
			// 那就多消耗点资源吧，谁让你不听话要设置权限。
			if(!empty($this->conf['forumaccesson'][$fid])) {
				$access = $this->forum_access->read($fid, $this->_user['groupid']); // 框架内部有变量缓存，此处不会重复查表。
				if($access && !$access['allowread']) {
					unset($threadlist[$k]);
					continue;
				}
			}
			
			$readtids .= ','.$thread['tid'];
			if($thread['top'] == 3) {
				unset($threadlist[$k]);
				$toplist[] = $thread;
				continue;
			}
		}
		
		$toplist = $page == 1 ? $this->get_toplist() : array();
		$toplist = array_filter($toplist);
		foreach($toplist as $k=>&$thread) {
			$this->thread->format($thread);
                        $readtids .= ','.$thread['tid'];
                }
                
		$readtids = substr($readtids, 1); 
		$click_server = $this->conf['click_server']."?db=tid&r=$readtids";
		
		$pages = misc::simple_pages('?index-index.htm', count($threadlist), $page, $pagesize);

		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('toplist', $toplist);
		$this->view->assign('click_server', $click_server);
		$this->view->assign('pages', $pages);
		
		// hook index_bbs_after.php
		
		$this->view->display('index_index.htm');
	}
	
	public function on_test() {
		$this->view->display('test_drag.htm');
	}
	
	private function get_toplist($forum = array()) {
		$fidtids = array();
		// 3 级置顶
		$fidtids = $this->get_fidtids($this->conf['toptids']);
		
		// 1 级置顶
		if($forum) {
			$fidtids += $this->get_fidtids($forum['toptids']);
		}
		
		$toplist = $this->thread->mget($fidtids);
		return $toplist;
	}
	
	private function get_fidtids($s) {
		$fidtids = array();
		if($s) {
			$fidtidlist = explode(' ', trim($s));
			foreach($fidtidlist as $fidtid) {
				if(empty($fidtid)) continue;
				$arr = explode('-', $fidtid);
				list($fid, $tid) = $arr;
				$fidtids["$fid-$tid"] = array($fid, $tid);
			}
		}
		return $fidtids;
	}
	//hook index_control_after.php
}

?>