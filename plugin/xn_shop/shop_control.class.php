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
		if(!$this->form_submit()) {
				
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
		} else {
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
				
				'pay_type'=>0,
				'pay_orderid'=>'',
				'pay_amount'=>0,
				'pay_email'=>'',
			);
			$orderid = $this->shop_order->xcreate($arr);
			
			$this->shop_cart->xtruncate(); // 清理购物车
			
			$this->message($orderid);
		}
	}
	
	public function on_pay() {
		$orderid = intval(core::gpc('orderid'));
		$order = $this->shop_order->read($orderid);
		empty($order) && $this->message('订单不存在。');
		$order['uid'] != $this->_user['uid'] && $this->message('该订单不是您的。');
		$order['status'] > 1 && $this->message('该订单已经支付过了。');
		$setting = $this->kv->get('shop_setting');
		empty($setting['enable']) && $this->message('在线支付关闭。');
		
		if(!$this->form_submit()) {
			// 选择支付方式，开始支付
			$this->view->assign('order', $order);
			$this->view->assign('orderid', $orderid);
			$this->view->assign('setting', $setting);
			$this->view->display('shop_pay.htm');
		} else {
			$pay_type = intval(core::gpc('pay_type', 'P'));
			$pay_amount = intval(core::gpc('pay_amount', 'P')); 	// 仅供参考。实际以 order.price 为准
			$pay_amount = $order['price'];				// 防止外部提交
			$uid = $this->_user['uid'];
			if($pay_type == 1) {
				empty($setting['alipay']['enable']) && $this->message('站点没有启用支付宝支付。');
				
		        	include BBS_PATH."plugin/xn_shop/alipay/alipay_submit.class.php";
		        	
		        	$parameter = array (
					"partner" => trim($setting['alipay']['partner']),
					"payment_type"	=> 1,				//支付类型
					"notify_url"	=> "?shop-alipaynotify.htm",	// 服务器异步通知页面路径
					"return_url"	=> "?shop-alipayreturn.htm",	// 页面跳转同步通知页面路径
					"seller_email"	=> $setting['alipay']['seller_email'],
					//"extra_common_param"	=> "shop-alipaynotify.htm",
					"out_trade_no"	=> $orderid,			// 外部订单号
					"subject"	=> "商品购买",
					"body"		=> "商品购买",
					"show_url"	=> "?shop.htm",			// 商品 url		
					"_input_charset" => 'utf-8',
				);
				// 担保交易
		        	if($setting['alipay']['type'] == 1) {
		        		$parameterarr = array(
			        		"service" 		=> "create_partner_trade_by_buyer",
						"price"			=> $order['price'],
						"quantity"		=> 1,
						"logistics_fee"		=> "0.00",		// 物流费用
						"logistics_type"	=> "EXPRESS",
						"logistics_payment"	=>  "SELLER_PAY",
						"receive_name"		=> $this->_user['username'],
						"receive_address"	=> $order['recv_address'],
						"receive_zip"		=> '',
						"receive_phone"		=> '',
						"receive_mobile"	=> $order['recv_mobile'],
					);
					$parameter = array_merge($parameter, $parameterarr);
				// 即时到帐
		        	} elseif($setting['alipay']['type'] == 2) {
		        		$parameterarr = array(
			        		"service" 		=> "create_direct_pay_by_user",
			        		//"total_fee"		=> $order['price'],
			        		"price"			=> $order['price'],
						"quantity"		=> 1,
			        		"anti_phishing_key"	=> '',
						"exter_invoke_ip"	=> $_SERVER['ip'],
					);
					$parameter = array_merge($parameter, $parameterarr);
				}
				$alipaySubmit = new AlipaySubmit($setting['alipay']);
				$html_text = $alipaySubmit->buildRequestForm($parameter, "get", "开始支付");
				echo $html_text;
				exit;		        
		        } elseif($pay_type == 2) {
		        	empty($setting['ebank']['enable']) && $this->message('站点没有启用网银支付。');
		        	
			        $v_mid = $setting['ebank']['mid'];		// 商户号，这里为测试商户号1001，替换为自己的商户号(老版商户号为4位或5位,新版为8位)即可   
				$v_url = "?shop-ebankreturn.htm";	// 请填写返回url,地址应为绝对路径,带有http协议
				$key = $setting['ebank']['key']; 				
		       		$v_oid = $orderid;	        	                
				$v_amount = $order['price'];
			        $remark1 = '';
			                        
				$v_moneytype = "CNY";                                            //币种		
				$text = $v_amount.$v_moneytype.$v_oid.$v_mid.$v_url.$key;        //md5加密拼凑串,注意顺序不能变
				$v_md5info = strtoupper(md5($text));                             //md5函数加密并转化成大写字母
				$style 			= '0';//网关模式0(普通列表)，1(银行列表中带外卡)
				$remark1 		= '';//备注字段1
				$remark2 		= '';//备注字段2
				
				$v_rcvname   = $order['recv_name'];	// 收货人
				$v_rcvaddr   = $order['recv_address'];	// 收货地址
				$v_rcvtel    = '';	// 收货人电话
				$v_rcvpost   = '';	// 收货人邮编
				$v_rcvemail  = '';	// 收货人邮件
				$v_rcvmobile = $order['recv_mobile'];	// 收货人手机号
				
				$v_ordername   = $v_rcvname;	// 订货人姓名
				$v_orderaddr   = $v_rcvaddr;	// 订货人地址
				$v_ordertel    = '';	// 订货人电话
				$v_orderpost   = '';	// 订货人邮编
				$v_orderemail  = '';	// 订货人邮件
				$v_ordermobile = $v_rcvmobile;	// 订货人手机号
								
				$forminfo = <<<EOT
				<!doctype html>
				<html>
					<head></head>
					<body>
						<form method="post" name="E_FORM" id="ebankform" action="https://Pay3.chinabank.com.cn/PayGate">
						<input type="hidden" name="v_mid"         value="$v_mid">
						<input type="hidden" name="v_oid"         value="$v_oid">
						<input type="hidden" name="v_amount"      value="$v_amount">
						<input type="hidden" name="v_moneytype"   value="$v_moneytype">
						<input type="hidden" name="v_url"         value="$v_url">
						<input type="hidden" name="v_md5info"     value="$v_md5info">				
						<input type="hidden" name="remark1"       value="$remark1">
						<input type="hidden" name="remark2"       value="$remark2">
						<input type="hidden" name="v_rcvname"      value="$v_rcvname">
						<input type="hidden" name="v_rcvtel"       value="$v_rcvtel">
						<input type="hidden" name="v_rcvpost"      value="$v_rcvpost">
						<input type="hidden" name="v_rcvaddr"      value="$v_rcvaddr">
						<input type="hidden" name="v_rcvemail"     value="$v_rcvemail">
						<input type="hidden" name="v_rcvmobile"    value="$v_rcvmobile">
						<input type="hidden" name="v_ordername"    value="$v_ordername">
						<input type="hidden" name="v_ordertel"     value="$v_ordertel">
						<input type="hidden" name="v_orderpost"    value="$v_orderpost">
						<input type="hidden" name="v_orderaddr"    value="$v_orderaddr">
						<input type="hidden" name="v_ordermobile"  value="$v_ordermobile">
						<input type="hidden" name="v_orderemail"   value="$v_orderemail">
						</form>
						<script>
		                                setTimeout(function() {  document.getElementById('ebankform').submit();}, 500);
		                                </script>
		                                <h1>正在连接网银 ... </h1>
		                        </body>
	                        </html>
EOT;
				echo $forminfo;
				exit;
			} elseif($pay_type == 3) {
				// todo: 财付通，待完成
			} elseif($pay_type == 4) {
				// 线下付款
				$this->message($setting['offline']['banklist']);
		        } else {
		        	$error['message'] = '查看相应的接口是否已启用!';
		        }
		}
	}
	
	public function on_alipayreturn() {
		array_shift($_GET);array_pop($_GET);array_pop($_GET);
		//foreach($_GET as &$v) $v = core::urldecode($v);
		
		include BBS_PATH."plugin/xn_shop/alipay/alipay_notify.class.php";	
		$setting = $this->kv->get('shop_setting');
		
		$alipayNotify = new Alipay($setting['alipay']);
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result) {
			$orderid = $out_trade_no = core::gpc('out_trade_no', 'R');	//商户订单号
			$trade_no = core::gpc('trade_no', 'R');				//支付宝交易号
			$trade_status = core::gpc('trade_status', 'R');			//交易状态
			$total_fee = core::gpc('total_fee', 'R');			//交易状态
			
			if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
				$order = $this->shop_order->read($orderid);
				$order['pay_type'] = 1;
				$order['pay_amount'] = $total_fee;
				$order['status'] = 1;
				$this->shop_order->update($order); // 支付时间是否要记一个？
				$this->message('支付成功，现在跳转到订单详情', 1, "?my-order-do-read-orderid-$orderid.htm");
			}
			echo "success";		// 请不要修改或删除
		} else {
			echo "failed";		// 请不要修改或删除
		}	
	}
	
	public function on_alipaynotify() {
		array_shift($_GET);array_pop($_GET);array_pop($_GET);
		//foreach($_GET as &$v) $v = core::urldecode($v);
		
		include BBS_PATH."plugin/xn_shop/alipay/alipay_notify.class.php";	
		$setting = $this->kv->get('shop_setting');
		
		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($setting['alipay']);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result) {
			$orderid = $out_trade_no = core::gpc('out_trade_no', 'R');	//商户订单号
			$trade_no = core::gpc('trade_no', 'R');				//支付宝交易号
			$trade_status = core::gpc('trade_status', 'R');			//交易状态
			$total_fee = core::gpc('total_fee', 'R');			//交易状态
			
			if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
				$order = $this->shop_order->read($orderid);
				$order['pay_type'] = 1;
				$order['pay_amount'] = $total_fee;
				$order['status'] = 1;
				$this->shop_order->update($order); // 支付时间是否要记一个？
			}
			echo "success";		// 请不要修改或删除
		} else {
			echo "failed";		// 请不要修改或删除
		}
	}
	
	public function on_ebankreturn() {
		include BBS_PATH."plugin/xn_shop/alipay/alipay_notify.class.php";	
		$setting = $this->kv->get('shop_setting');
		
		$v_mid 		= $setting['ebank']['mid'];		// 商户号
		$key 		= $setting['ebank']['key']; 	
		$v_oid     	= trim(core::gpc('v_oid', 'P'));       // 商户发送的v_oid定单编号   
		$v_pmode   	= trim(core::gpc('v_pmode', 'P'));    // 支付方式（字符串）   
		$v_pstatus 	= trim(core::gpc('v_pstatus', 'P'));   //  支付状态 ：20（支付成功）；30（支付失败）
		$v_pstring 	= trim(core::gpc('v_pstring', 'P'));   // 支付结果信息 ： 支付完成（当v_pstatus=20时）；失败原因（当v_pstatus=30时,字符串）； 
		$v_amount  	= trim(core::gpc('v_amount', 'P'));     // 订单实际支付金额
		$v_moneytype  	= trim(core::gpc('v_moneytype', 'P')); //订单实际支付币种    
		$remark1   	= trim(core::gpc('remark1', 'P'));      //备注字段1
		$remark2   	= trim(core::gpc('remark2', 'P'));     //备注字段2
		$v_md5str  	= trim(core::gpc('v_md5str', 'P'));   //拼凑后的MD5校验值 
		
		$md5string = strtoupper(md5($v_oid.$v_pstatus.$v_amount.$v_moneytype.$key));
		if($v_md5str == $md5string){
			if($v_pstatus == "20"){
				$orderid = $v_oid;
				$order = $this->shop_order->read($orderid);
				$order['pay_type'] = 2;
				$order['pay_amount'] = $v_amount;
				$order['status'] = 1;
				$this->shop_order->update($order); // 支付时间是否要记一个？
				$this->message('支付成功，现在跳转到订单详情', 1, "?my-order-do-read-orderid-$orderid.htm");
				//echo "ok";
			}else{
				echo 'error';
			}		
		} else {
			echo 'error';
		}
		exit;
	}
	
	// 返回订单状态, ajax 检测订单支付状态的时候用
	public function on_orderinfo() {
		$orderid = intval(core::gpc('orderid'));
		$order = $this->shop_order->read($orderid);
		empty($order) && $this->message('订单不存在。');
		$order['uid'] != $this->_user['uid'] && $this->message('该订单不是您的。');
		$setting = $this->kv->get('shop_setting');
		empty($setting['enable']) && $this->message('在线支付关闭。');
		
		$this->message($order);
	}
	
	// 显示购物车
	public function on_cart() {
		$do = core::gpc('do');
		empty($do) && $do = 'list';
		if($do == 'list') {
			$n = count($this->cart_shop_list);
			$this->view->display('shop_cart_ajax.htm');
		} elseif($do == 'delete') {
			$goodid = intval(core::gpc('goodid'));
			$n = $this->shop_cart->xdelete($goodid);
			$this->message($n);
		} elseif($do == 'add') {
			$goodid = intval(core::gpc('goodid'));
			$amount = intval(core::gpc('amount'));
			$good = $this->shop_good->read($goodid);
			empty($good) && $this->message('商品不存在。');
			$n = $this->shop_cart->xcreate($goodid, $amount);
			$this->message($n);
		}
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