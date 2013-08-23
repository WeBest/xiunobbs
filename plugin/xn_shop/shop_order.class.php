<?php

/*
 * Copyright (C) xiuno.com
 */

class shop_order extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'shop_order';
		$this->primarykey = array('orderid');
		$this->maxcol = 'orderid';
	}
	
	public function get_list($page = 1, $pagesize = 20) {
		$start = ($page - 1) * $pagesize;
		$orderlist = $this->index_fetch(array(), array('orderid'=>-1), $start, $pagesize);
		foreach($orderlist as &$order) {
			$this->format($order);
		}
		return $orderlist;
	}
	
	public function get_list_by_uid($uid, $page = 1, $pagesize = 20) {
		$start = ($page - 1) * $pagesize;
		$orderlist = $this->index_fetch(array('uid'=>$uid), array('orderid'=>-1), $start, $pagesize);
		foreach($orderlist as &$order) {
			$this->format($order);
		}
		return $orderlist;
	}
	
	// 获取收货人地址列表
	public function get_recv_address_list() {
	
	}
	
	public function xcreate($arr) {
		$orderid = $this->create($arr);
		$json_amount = core::json_decode($arr['json_amount']);
		foreach($json_amount as $shopid=>$amount) {
			$good = $this->shop_good->read($shopid);
			if(empty($good)) continue;
			$good['orders']++;
			$this->shop_good->update($good);
		}
		return $orderid;
	}
	
	// 删除订单
	public function xdelete($orderid) {
		$order = $this->read($order);
		if(empty($order)) return 0;
		$json_amount = core::json_decode($order['json_amount']);
		foreach($json_amount as $goodid=>$amount) {
			$good = $this->shop_good->read($goodid);
			$good['orders']--;
			$this->shop_good->update($good);
		}
		$n = $this->delete($goodid);
		return $n;
	}
	
	// 格式化订单。
	public function format(&$order) {
		$order['goodarr'] = array();
		$order['goodlist'] = array();
		$order['json_amount'] = core::json_decode($order['json_amount']);
		$totalprice = 0;
		foreach($order['json_amount'] as $goodid=>$amount) {
			$arr = $this->shop_good->read($goodid);
			$this->shop_good->format($arr);
			$arr['amount'] = $amount;
			$arr['amountprice'] = $amount * $arr['price'];
			$order['goodlist'][] = $arr;
			$totalprice += $arr['amountprice'];
		}
		$order['user'] = $this->user->read($order['uid']);
		$order['dateline_fmt'] = misc::humandate($order['dateline']);
		
		$status = array(0=>'待支付', 1=>'已支付，等待发货', 2=>'已发货，等待收货', 3=>'已收货，交易完毕', 4=>'无效订单');
		$order['status_fmt'] = $status[$order['status']];
		$order['totalprice'] = $totalprice;
	}
}
?>