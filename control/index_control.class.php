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

		$setting = $this->kv->get('shop_setting');
		// http://www.yegenyou.com/shop-alipayreturn.htm?body=%E5%95%86%E5%93%81%E8%B4%AD%E4%B9%B0&buyer_email=axiuno%40gmail.com&buyer_id=2088002093746313&exterface=create_direct_pay_by_user&is_success=T&notify_id=RqPnCoPT3K9%252Fvwbh3I72LheDYDTEvTJcgVeS0cnIBTCRibmzWmVLsBpG7kQpYJFH2Adv&notify_time=2013-08-24+19%3A27%3A13&notify_type=trade_status_sync&out_trade_no=10001&payment_type=1&seller_email=248802407%40qq.com&seller_id=2088701071709204&subject=%E5%95%86%E5%93%81%E8%B4%AD%E4%B9%B0&total_fee=1.00&trade_no=2013082457477731&trade_status=TRADE_SUCCESS&sign=38e4804656dbcc48791b693390905d54&sign_type=MD5
		$_GET = array (
  'body' => '商品购买',
  'buyer_email' => 'axiuno@gmail.com',
  'buyer_id' => '2088002093746313',
  'exterface' => 'create_direct_pay_by_user',
  'is_success' => 'T',
  'notify_id' => 'RqPnCoPT3K9%2Fvwbh3I72LheDZ0xnYaFE92XSfEy%2FC5DqjmTOWcGwsS5R1uKzq3vHec92',
  'notify_time' => '2013-08-24 19:37:18',
  'notify_type' => 'trade_status_sync',
  'out_trade_no' => '10003',
  'payment_type' => '1',
  'seller_email' => '248802407@qq.com',
  'seller_id' => '2088701071709204',
  'subject' => '商品购买',
  'total_fee' => '1.00',
  'trade_no' => '2013082457502031',
  'trade_status' => 'TRADE_SUCCESS',
  'sign' => 'edc41f083797743dd44bc2184ef85c8f',
  'sign_type' => 'MD5',
);
	
		//$_GET['notify_id'] = urlencode($_GET['notify_id']);
		foreach($_GET as &$v) $v = urlencode($v);
		//foreach($_GET as &$v) $v = urldecode($v);
		//foreach($_GET as &$v) $v = iconv('utf-8', 'gbk', $v);
		//foreach($_GET as &$v) $v = iconv('gbk', 'utf-8', $v);
		//foreach($_GET as &$v) $v = iconv('utf-8', 'gbk', urldecode($v));
		
		include BBS_PATH."plugin/xn_shop/alipay/alipay_notify.class.php";	
		$setting = $this->kv->get('shop_setting');
		
		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($setting['alipay']);
		$verify_result = $alipayNotify->verifyReturn();
		print_r($_GET);
		var_dump($verify_result);
		exit;
		
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