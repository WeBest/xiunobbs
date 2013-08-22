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
	}
	
	public function on_index() {
		//$this->view->display('xn_shop.htm');
		$this->on_list();
	}
	
	// 商品列表
	public function on_list() {
		$catearr = $this->shop_cate->get_arr();
		
	}
	
	public function on_good() {
		$goodid = intval(core::gpc('goodid'));
		$good = $this->shop_good->read($goodid);
		$this->shop_good->format($good);
		empty($good) && $this->message('商品不存在。');
		$imglist = $this->shop_image->get_loop_list($goodid);
		$this->view->assign('imglist', $imglist);
		$this->view->assign('good', $good);
		$this->view->display('shop_good_read.htm');
	}
}

?>