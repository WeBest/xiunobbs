<?php

/*
 * Copyright (C) xiuno.com
 */

// 购物车, cookie 实现。
class shop_cart extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
	}
	
	// 从 cookie 中获取
	public function get_list() {
		$cookie = core::gpc($this->conf['cookie_pre'].'cart', 'C');
		$arr = $cookie ? core::json_decode($cookie) : array();
		$goodlist = array();
		foreach($arr as $goodid=>$amount) {
			$good = $this->shop_good->read($goodid);
			if(empty($good)) continue;
			$this->shop_good->format($good);
			$good['amount'] = $amount;
			$good['amountprice'] = $amount * $good['price'];
			$goodlist[$good['goodid']] = $good;
		}
		return $goodlist;
	}
	
	// 保存到 cookie
	public function xcreate($goodid, $amount) {
		$cookie = core::gpc($this->conf['cookie_pre'].'cart', 'C');
		$arr = $cookie ? core::json_decode($cookie) : array();
		if(count($arr) > 10) return -1;
		$arr[$goodid] = $amount;
		misc::setcookie($this->conf['cookie_pre'].'cart', core::json_encode($arr), $_SERVER['time'] + 36000, '/');
		return count($arr);
	}
	
	// 从 cookie 中删除
	public function xdelete($goodid) {
		$cookie = core::gpc($this->conf['cookie_pre'].'cart', 'C');
		$arr = $cookie ? core::json_decode($cookie) : array();
		if(isset($arr[$goodid])) unset($arr[$goodid]);
		misc::setcookie($this->conf['cookie_pre'].'cart', core::json_encode($arr), $_SERVER['time'] + 36000, '/');
		return count($arr);
	}
	
	// 清空
	public function xtruncate() {
		misc::setcookie($this->conf['cookie_pre'].'cart', '', 0, '/');
		return TRUE;
	}
}
?>