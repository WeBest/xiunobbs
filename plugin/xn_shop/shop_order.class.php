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
	
	public function format(&$shop) {
		
	}
}
?>