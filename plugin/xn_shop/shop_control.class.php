<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class shop_control extends common_control {
	
	public $cart_shop_list = array(); // 购物车内的商品
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['shop'] = ' class="checked"';
		
		$this->init_cart();
	}
	
	public function on_index() {
		//$this->view->display('xn_shop.htm');
		$this->cart_shop_list = $this->on_list();
	}
	
	// 商品列表
	public function on_list() {
		$cateid = intval(core::gpc('cateid'));
		$catearr = $this->shop_cate->get_arr();
		!isset($_GET['cateid']) && $catearr && $cateid = key($catearr);
		
		$cate = $this->shop_cate->read($cateid);
		$this->_checked['cateid_'.$cateid] = ' class="checked"';
		
		$goods = empty($cate) ? 0 : $cate['goods'];
		$catearr[0] = '全部';
		ksort($catearr);
		$cateselect = form::get_select('cateid', $catearr, $cateid);
		
		$pagesize = 20;
		$page = misc::page();
		$pages = misc::pages("?shop-list-cateid-$cateid.htm", $goods, $page, $pagesize);
		$goodlist = $this->shop_good->get_list_by_cateid($cateid, $page);
		
		$this->view->assign('cate', $cate);
		$this->view->assign('catearr', $catearr);
		$this->view->assign('cateid', $cateid);
		$this->view->assign('goodlist', $goodlist);
		$this->view->assign('cateselect', $cateselect);
		$this->view->display('shop_good_list.htm');
	}
	
	public function on_good() {
		$goodid = intval(core::gpc('goodid'));
		$good = $this->shop_good->read($goodid);
		empty($good) && $this->message('商品不存在。');
		$good['views']++;
		$this->shop_good->update($good);
		
		$cate = $this->shop_cate->read($good['cateid']);
		
		$imglist = $this->shop_image->get_loop_list($goodid);
		$this->shop_good->format($good);
		$this->view->assign('cate', $cate);
		$this->view->assign('goodid', $goodid);
		$this->view->assign('imglist', $imglist);
		$this->view->assign('good', $good);
		$this->view->display('shop_good_read.htm');
	}
	
	// 下订单，把购物车内的数据导出来。
	public function on_buy() {
		$goodlist = array(); // 购物车内的商品列表
		$goodid = intval(core::gpc('goodid'));
		
		if($goodid) {
			$good = $this->shop_good->read($goodid);
			empty($good) && $this->message('商品不存在。');
			
			$amount = intval(core::gpc('amount'));
			
			$good['amount'] = $amount;
			$good['amountprice'] = $amount * $good['price'];
			$goodlist = array($goodid=>$good);	
			
			$cate = $this->shop_cate->read($good['cateid']);
			$this->view->assign('good', $good);
			$this->view->assign('cate', $cate);
			$this->shop_good->format($good);
		} else {
			$goodlist = $this->shop_cart->get_list();
			empty($goodlist) && $this->message('请选择商品。');
		}
		
		$totalprice = 0;
		foreach ($goodlist as $_good) $totalprice += $_good['amountprice'];
		
		$imglist = $this->shop_image->get_loop_list($goodid);
		
		$this->view->assign('goodid', $goodid);
		$this->view->assign('imglist', $imglist);
		$this->view->assign('totalprice', $totalprice);
		$this->view->assign('goodlist', $goodlist);
		$this->view->display('shop_buy.htm');
	}
	
	// ajax 提交订单
	public function on_buysubmit() {
		// 如果有提交数据，处理提交
		if($this->form_submit()) {
			$recv_address = core::gpc('recv_address', 'P');
			$recv_name = core::gpc('recv_name', 'P');
			$recv_mobile = core::gpc('recv_mobile', 'P');
			$recv_comment = core::gpc('recv_comment', 'P');
			$amountarr = (array)core::gpc('amount', 'P');
			$totalprice = 0;
			
			$shopnum = 0;
			foreach($amountarr as $goodid=>$amount) {
				$good = $this->shop_good->read($goodid);
				if(empty($good)) continue;
				$totalprice += $good['price'] * $amount;
				$shopnum++;
			}
			empty($shopnum) && $this->message('请选择有效商品ID。', 0);
			
			list($year, $month, $day) = explode('-', date('y-n-j', $_SERVER['time']));
			
			$json_amount = core::json_encode($amountarr);
			if(utf8::strlen($json_amount) > 255) $this->message('商品个数太多。', 0);
			
			// 保存到订单
			$arr = array(
				'uid'=>$this->_user['uid'],
				'dateline'=>$_SERVER['time'],
				'price'=>$totalprice,
				'year'=>$year,
				'month'=>$month,
				'day'=>$day,
				'status'=>0,
				
				'json_amount'=>$json_amount,
				
				'recv_address'=>$recv_address,
				'recv_mobile'=>$recv_mobile,
				'recv_name'=>$recv_name,
				'recv_comment'=>$recv_comment,
				'admin_comment'=>'',
				
				'alipay_email'=>'',
				'alipay_orderid'=>'',
				'alipay_fee'=>'',
				'alipay_receive_name'=>'',
				'alipay_receive_phone'=>'',
				'alipay_receive_mobile'=>'',
			);
			$orderid = $this->shop_order->xcreate($arr);
			
			$this->shop_cart->xtruncate(); // 清理购物车
			
			$this->message($orderid);
		} else {
			$this->message('提交订单失败。', 0);
		}
	}
	
	// 显示购物车
	public function on_cart() {
		$n = count($this->cart_shop_list);
		$this->view->display('shop_cart_ajax.htm');
	}
	
	// 从购物车中删除物品
	public function on_deletecart() {
		$goodid = intval(core::gpc('goodid'));
		$n = $this->shop_cart->xdelete($goodid);
		$this->message($n);
	}
	
	public function on_addcart() {
		$goodid = intval(core::gpc('goodid'));
		$amount = intval(core::gpc('amount'));
		$good = $this->shop_good->read($goodid);
		empty($good) && $this->message('商品不存在。');
		
		// 商品的总数
		$n = $this->shop_cart->xcreate($goodid, $amount);
		
		$this->message($n);
	}
	
	// cart 的状态
	public function init_cart() {
		$shoplist = $this->shop_cart->get_list();
		$n = count($shoplist);
		$this->view->assign('cart_shop_number', $n);
		$this->view->assign('cart_shop_list', $shoplist);
	}
}

?>