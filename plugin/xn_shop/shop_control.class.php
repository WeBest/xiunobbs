<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class shop_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['shop'] = ' class="checked"';
		
		$this->init_cart();
	}
	
	public function on_index() {
		//$this->view->display('xn_shop.htm');
		$this->on_list();
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
		$this->view->assign('imglist', $imglist);
		$this->view->assign('good', $good);
		$this->view->display('shop_good_read.htm');
	}
	
	// 下订单，把购物车内的数据导出来。
	public function on_buy() {
		$goodlist = array(); // 购物车内的商品列表
		$goodid = intval(core::gpc('goodid'));
		$amount = intval(core::gpc('amount'));
		$good = $this->shop_good->read($goodid);
		empty($good) && $this->message('商品不存在。');
		
		if($goodid) {
			$good['amount'] = $amount;
			$goodlist = array($goodid=>$good);	
		} else {
			$goodlist = $this->shop_cart->get_list();
		}
		
		$cate = $this->shop_cate->read($good['cateid']);
		
		$imglist = $this->shop_image->get_loop_list($goodid);
		$this->shop_good->format($good);
		
		// 如果有提交数据，处理提交
		if($this->form_submit()) {
			print_r($_POST);exit;
		}
		
		$this->view->assign('goodid', $goodid);
		$this->view->assign('cate', $cate);
		$this->view->assign('imglist', $imglist);
		$this->view->assign('good', $good);
		$this->view->assign('goodlist', $goodlist);
		$this->view->display('shop_buy.htm');
	}
	
	public function on_addcart() {
		$goodid = intval(core::gpc('goodid'));
		$amount = intval(core::gpc('amount'));
		$good = $this->shop_good->read($goodid);
		empty($good) && $this->message('商品不存在。');
		
		$this->shop_cart->xcreate($goodid, $amount);
		
		$this->message('保存成功。');
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