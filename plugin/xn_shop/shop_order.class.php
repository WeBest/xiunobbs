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
	
	public function get_list($page = 1) {
		$pagesize = 20;
		$start = ($page - 1) * $pagesize;
		$orderlist = $this->index_fetch(array(), array('orderid'=>-1), $start, $pagesize);
		return $orderlist;
	}
	
	public function xcreate($arr) {
		$orderid = $this->create($arr);
		$good = $this->shop_good->read($arr['goodid']);
		$good['orders']++;
		$this->shop_good->update($good);
		return $orderid;
	}
	
	public function xdelete($orderid) {
		$n = $this->delete($goodid);
		if($n > 0) {
			$good = $this->shop_good->read($arr['goodid']);
			$good['orders']--;
			$this->shop_good->update($good);
		}
		// 删除评论
		return $n;
	}
	
	// 格式化订单。
	public function format(&$order) {
		$order['goodarr'] = array();
		$order['goodlist'] = array();
		$order['goodarr'] = core::json_decode($good['json_good']);
		foreach($order['goodarr'] as $goodid=>$amount) {
			$arr = $this->shop_good->read($goodid);
			$this->shop_good->format($arr);
			$arr['amount'] = $amount;
			$order['goodlist'][] = $arr;
		}
	}
}
?>