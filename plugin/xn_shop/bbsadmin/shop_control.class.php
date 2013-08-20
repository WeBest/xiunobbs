<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

// 此 control 不检查 xss，给 swfupload 放行
define('XIUNO_SKIP_CHECK_XSS', 1);

include BBS_PATH.'admin/control/admin_control.class.php';

class shop_control extends admin_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->check_admin_group();
	}
	
	// 考虑默认过期时间
	public function on_index() {
		// 管理商品，管理分类，管理订单
		
		//$this->view->display('xn_shop.htm');
		
		$this->on_good();
	}
	
	public function on_good() {
		$this->_checked['shop_good'] = 'class="checked"';
		$do = core::gpc('do');
		empty($do) && $do = 'list';
		if($do == 'list') {
			$cateid = intval(core::gpc('cateid'));
			$catearr = $this->shop_cate->get_arr();
			$cateselect = form::get_select('cateid', $catearr, $cateid);
			$this->view->assign('cateselect', $cateselect);
			$this->view->display('xn_shop_good_list.htm');
		} elseif($do == 'create') {
			$goodid = intval(core::gpc('goodid', 'P'));
			if(!$this->form_submit()) {
				$goodid = $this->shop_good->maxid() + 1;
				$this->view->assign('goodid', $goodid);
				$this->view->display('xn_shop_good_create.htm');
			} else {
				$subject = core::gpc('subject', 'P');
				$message = core::gpc('message', 'P');
				$arr = array(
					'goodid'=>$goodid,
					'subject'=>$subject,
					'message'=>$message,
					'dateline'=>$_SERVER['time'],
				);
				$this->shop_good->xcreate($arr);
			}
			
		}
	}
	
	public function on_cate() {
		$this->_checked['shop_cate'] = 'class="checked"';
		$do = core::gpc('do');
		empty($do) && $do = 'list';
		if($do == 'list') {
			$catelist = $this->shop_cate->get_list();
			$catearr = $this->shop_cate->get_arr();
			$cateid = intval(core::gpc('cateid'));
			if(empty($cateid)) {
				$first = current($catearr);
				$first && $cateid = $first['cateid'];
			}
			
			$newcateid = $this->shop_cate->maxid() + 1;
			$cateselect = form::get_select('cateid', $catearr, $cateid);
			$this->view->assign('newcateid', $newcateid);
			$this->view->assign('catelist', $catelist);
			$this->view->assign('cateselect', $cateselect);
			$this->view->display('xn_shop_cate.htm');
		// 逐条
		} elseif($do == 'update') {
			$cateid = intval(core::gpc('cateid'));
			$rank = intval(core::gpc('rank'));
			$name = core::urldecode(core::gpc('name'));
			$cate = $this->shop_cate->read($cateid);
			if(empty($cate)) {
				$this->message('分类不存在', 0);
			}
			$cate['rank'] = $rank;
			$cate['name'] = $name;
			$this->shop_cate->xupdate($cate);
			$this->message('更新成功。');
		} elseif($do == 'delete') {
			$cateid = intval(core::gpc('cateid'));
			$this->shop_cate->xdelete($cateid);
			$this->message('删除成功。');
		} elseif($do == 'create') {
			$cateid = intval(core::gpc('cateid'));
			$rank = intval(core::gpc('rank'));
			$name = core::urldecode(core::gpc('name'));
			$arr = array(
				'cateid'=>$cateid,
				'rank'=>$rank,
				'name'=>htmlspecialchars($name),
			);
			$this->shop_cate->xcreate($arr);
			$this->message('创建成功。');
		}
	}
	
	// 创建分类
	public function on_order() {
		
	}
}

?>