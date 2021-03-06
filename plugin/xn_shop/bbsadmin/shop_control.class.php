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
		
		//$this->view->display('shop.htm');
		
		$this->on_good();
	}
	
	public function on_good() {
		$this->_checked['shop_good'] = 'class="checked"';
		$do = core::gpc('do');
		empty($do) && $do = 'list';
		if($do == 'list') {
			$cateid = intval(core::gpc('cateid'));
			$catearr = $this->shop_cate->get_arr();
			!isset($_GET['cateid']) && $catearr && $cateid = key($catearr);
			
			$cate = $this->shop_cate->read($cateid);
			
			$goods = empty($cate) ? 0 : $cate['goods'];
			$catearr[0] = '全部';
			ksort($catearr);
			$cateselect = form::get_select('cateid', $catearr, $cateid);
			
			$pagesize = 20;
			$page = misc::page();
			$pages = misc::pages("?shop-good-do-list-cateid-$cateid.htm", $goods, $page, $pagesize);
			$goodlist = $this->shop_good->get_list_by_cateid($cateid, $page);
			
			$this->view->assign('cateid', $cateid);
			$this->view->assign('goodlist', $goodlist);
			$this->view->assign('cateselect', $cateselect);
			// hook admin_shop_good_list_end.php
			$this->view->display('shop_good_list.htm');
		} elseif($do == 'create') {
			$goodid = intval(core::gpc('goodid', 'P'));
			if(!$this->form_submit()) {
				$cateid = intval(core::gpc('cateid'));
				$goodid = $this->shop_good->maxid() + 1;
				
				$catearr = $this->shop_cate->get_arr();
				empty($cateid) && $catearr && $cateid = key($catearr);
				$cateselect = form::get_select('cateid', $catearr, $cateid);
				
				
				$this->view->assign('goodid', $goodid);
				$this->view->assign('cateselect', $cateselect);
				// hook admin_shop_good_create_submit_before.php
				$this->view->display('shop_good_create.htm');
			} else {
				$goodid = intval(core::gpc('goodid'));
				$cateid = intval(core::gpc('cateid', 'P'));
				$name = core::gpc('name', 'P');
				$message = core::gpc('message', 'P');
				$stocks = intval(core::gpc('stocks', 'P'));
				$price = intval(core::gpc('price', 'P'));
				$cover = $this->shop_image->get_cover($goodid);
				$arr = array(
					'goodid'=>$goodid,
					'cateid'=>$cateid,
					'name'=>$name,
					'message'=>$message,
					'cover'=>$cover,
					'price'=>$price,
					'dateline'=>$_SERVER['time'],
					'stocks'=>$stocks,
					'orders'=>0,
					'replies'=>0,
					'views'=>0,
					'rank'=>100,
				);
				// hook admin_shop_good_create_submit_after.php
				$this->shop_good->xcreate($arr);
				$this->message('添加商品成功。');
			}
		} elseif($do == 'update') {
			$goodid = intval(core::gpc('goodid'));
			if(!$this->form_submit()) {
				$good = $this->shop_good->read($goodid);
				$this->check_good_exists($good);
				
				$catearr = $this->shop_cate->get_arr();
				$cateselect = form::get_select('cateid', $catearr, $good['cateid']);
				
				$imglist = $this->shop_image->get_list_by_goodid($goodid);
				$good['message'] = htmlspecialchars($good['message']);
				$good['img1'] = $this->shop_image->get_seq($imglist, 1);
				$good['img2'] = $this->shop_image->get_seq($imglist, 2);
				$good['img3'] = $this->shop_image->get_seq($imglist, 3);
				$good['img4'] = $this->shop_image->get_seq($imglist, 4);
				$good['img5'] = $this->shop_image->get_seq($imglist, 5);
				
				$this->shop_good->format($good);
				$this->view->assign('good', $good);
				$this->view->assign('goodid', $goodid);
				$this->view->assign('cateselect', $cateselect);
				// hook admin_shop_good_update_submit_before.php
				$this->view->display('shop_good_update.htm');
			} else {
				$cateid = intval(core::gpc('cateid', 'P'));
				$name = core::gpc('name', 'P');
				$message = core::gpc('message', 'P');
				$stocks = intval(core::gpc('stocks', 'P'));
				$price = intval(core::gpc('price', 'P'));
				$cover = $this->shop_image->get_cover($goodid);
				$good = $this->shop_good->read($goodid);
				$this->check_good_exists($good);
				$good['cateid'] = $cateid;
				$good['name'] = $name;
				$good['message'] = $message;
				$good['cover'] = $cover;
				$good['price'] = $price;
				$good['stocks'] = $stocks;
				// hook admin_shop_good_update_submit_after.php
				$this->shop_good->xupdate($good);
				$this->message('更新商品成功。');
			}
		} elseif($do == 'updaterank') {
			$goodid = intval(core::gpc('goodid'));
			$rank = intval(core::gpc('rank'));
			$good = $this->shop_good->read($goodid);
			$this->check_good_exists($good);
			$good['rank'] = $rank;
			$this->shop_good->update($good);
			$this->message('更新顺序成功。');
		} elseif($do == 'delete') {
			$goodid = intval(core::gpc('goodid'));
			// hook admin_shop_good_delete_before.php
			$this->shop_good->xdelete($goodid);
			$this->message('删除成功！');
		}
	}
	
	// 获取第一张
	/*private function get_cover($goodid) {
		$diradd = image::get_dir($goodid);
		$attachpath = $this->conf['upload_path'].'attach_shop/'.$diradd;
		$files = misc::scandir($attachpath);
		foreach($files as $file) {
			if(preg_match("#^{$goodid}_#", $file)) {
				return $this->conf['upload_url'].'attach_shop/'.$diradd.'/'.$file;
			}
		}
		return '';
	}*/
	
	public function on_cate() {
		$this->_checked['shop_cate'] = 'class="checked"';
		$do = core::gpc('do');
		empty($do) && $do = 'list';
		if($do == 'list') {
			$catelist = $this->shop_cate->get_list();
			$catearr = $this->shop_cate->get_arr();
			$cateid = intval(core::gpc('cateid'));
			empty($cateid) && $cateid = key($catearr);
			
			$newcateid = $this->shop_cate->maxid() + 1;
			$cateselect = form::get_select('cateid', $catearr, $cateid);
			$this->view->assign('newcateid', $newcateid);
			$this->view->assign('catelist', $catelist);
			$this->view->assign('cateselect', $cateselect);
			$this->view->display('shop_cate.htm');
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
	
	// 订单管理
	public function on_order() {
		
		$this->_checked['shop_order'] = 'class="checked"';
		$do = core::gpc('do');
		empty($do) && $do = 'list';
		if($do == 'list') {
				
			$pagesize = 20;
			$page = misc::page();
			$n = $this->shop_order->count();
			$pages = misc::pages("?shop-order-do-list.htm", $n, $page, $pagesize);
			$orderlist = $this->shop_order->get_list($page, $pagesize);
			
			$this->view->assign('page', $page);
			$this->view->assign('pages', $pages);
			$this->view->assign('orderlist', $orderlist);
			$this->view->assign('orderlist', $orderlist);
			$this->view->display('shop_order_list.htm');
		} elseif($do == 'delete') {
			$orderid = intval(core::gpc('orderid'));
			$order = $this->shop_order->read($orderid);
			empty($order) && $this->message('订单不存在。');
			
			$this->shop_order->xdelete($orderid);
			$this->message('删除成功。');
		} elseif($do == 'read') {
			$orderid = intval(core::gpc('orderid'));
			$order = $this->shop_order->read($orderid);
			empty($order) && $this->message('订单不存在。');
			
			$this->shop_order->format($order);
			
			$this->view->assign('orderid', $orderid);
			$this->view->assign('order', $order);
			$this->view->display('shop_order_read.htm');
		// 更新订单
		} elseif($do == 'update') {
			$orderid = intval(core::gpc('orderid'));
			$order = $this->shop_order->read($orderid);
			empty($order) && $this->message('订单不存在。');
			
			$recv_address = core::gpc('recv_address', 'P');
			$recv_name = core::gpc('recv_name', 'P');
			$recv_mobile = core::gpc('recv_mobile', 'P');
			$recv_comment = core::gpc('recv_comment', 'P');
			$admin_comment = core::gpc('admin_comment', 'P');
			$status = core::gpc('status', 'P');
			
			$order['recv_address'] = $recv_address;
			$order['recv_name'] = $recv_name;
			$order['recv_mobile'] = $recv_mobile;
			$order['recv_comment'] = $recv_comment;
			$order['admin_comment'] = $admin_comment;
			$order['status'] = $status;
			$this->shop_order->update($order);
			
			$this->message('更新成功');
		}
	}
	
	// 编辑器依赖上传图片
	public function on_uploadimage() {
		$file = array();
		!empty($_FILES['upfile']) && $file = $_FILES['upfile'];
		!empty($_FILES['Filedata']) && $file = $_FILES['Filedata'];
		empty($file) && $this->uploaderror('没有上传文件。');
		
		$uploadpath = $this->conf['upload_path'].'attach_shop/';
		$uploadurl = $this->conf['upload_url'].'attach_shop/';
		
		$goodid = intval(core::gpc('goodid'));
		$seq = intval(core::gpc('seq'));
		$good = $this->shop_good->read($goodid);
		$dateline = empty($good) ? $_SERVER['time'] : $good['dateline'];
		
		
		!is_dir($uploadpath) && mkdir($uploadpath, 0777);
		$diradd = image::set_dir($goodid, $uploadpath);
		
		// 对付一些变态的 iis 环境， is_file() 无法检测无权限的目录。
		$tmpfile = FRAMEWORK_TMP_TMP_PATH.md5(rand(0, 1000000000).$_SERVER['time'].$_SERVER['ip']).'.tmp';
		$succeed = IN_SAE ? copy($file['tmp_name'], $tmpfile) : move_uploaded_file($file['tmp_name'], $tmpfile);
		if(!$succeed) {
			$this->uploaderror('移动临时文件错误，请检查临时目录的可写权限。');
		}
		
		$file['tmp_name'] = $tmpfile;
		core::htmlspecialchars($file['name']);
		$filetype = $this->attach->get_filetype($file['name']);
		
		!is_dir($uploadpath) && mkdir($uploadpath, 0777);
		
		if($filetype != 'image') {
			$this->uploaderror('请您上传图片！');
		}
		// 处理文件
		$imginfo = getimagesize($file['tmp_name']);
		
		$suffix = $seq ? $seq : rand(1, 99999999);
		if($imginfo[2] == 1) {
			$fileurl = $diradd.'/'.$goodid.'_'.$suffix.'.gif';
			$thumbfile = $uploadpath.$fileurl;
			
			if($seq > 0) {
				image::clip_thumb($file['tmp_name'], $thumbfile, 1600, 800);
				$thumbfile2 = image::thumb_name($thumbfile);
				image::clip_thumb($file['tmp_name'], $thumbfile2, 560, 280);
				$thumbfile3 = image::thumb_name($thumbfile2);
				image::clip_thumb($file['tmp_name'], $thumbfile3, 300, 150);
			} else {
				copy($file['tmp_name'], $thumbfile);
			}
			
			$r['filesize'] = filesize($file['tmp_name']);
			$r['width'] = $imginfo[0];
			$r['height'] = $imginfo[1];
			$r['fileurl'] = $fileurl;
		} else {
			$destext = image::ext($file['name']);
			$fileurl = $diradd.'/'.$goodid.'_'.$suffix.'.'.$destext;
			$thumbfile = $uploadpath.$fileurl;
			
			if($seq > 0) {
				image::clip_thumb($file['tmp_name'], $thumbfile, 1600, 800);
				$thumbfile2 = image::thumb_name($thumbfile);
				image::clip_thumb($file['tmp_name'], $thumbfile2, 560, 280);
				$thumbfile3 = image::thumb_name($thumbfile2);
				image::clip_thumb($file['tmp_name'], $thumbfile3, 300, 150);
			} else {
				image::thumb($file['tmp_name'], $thumbfile, 1920, 200000);
			}
			
			$imginfo = getimagesize($thumbfile);
			$r['filesize'] = filesize($thumbfile);
			$r['width'] = $imginfo[0];
			$r['height'] = $imginfo[1];
			$r['fileurl'] = $fileurl;
		}
		
		// db 里记录一下 image
		$arr = array(
			'goodid'=>$goodid,
			'seq'=>$seq,
			'fileurl'=>$fileurl,
			'width'=>$r['width'],
			'height'=>$r['height'],
		);
		$this->shop_image->xcreate($arr);
		
		is_file($file['tmp_name']) && unlink($file['tmp_name']);
		$title = htmlspecialchars(core::gpc('pictitle', 'P'));
		if($seq > 0) $r['fileurl'] = image::thumb_name($r['fileurl']);
		
		// hook admin_shop_uploadimage_end.php
		
		echo '{"url":"' . $uploadurl.$r['fileurl'] . '","title":"' . $title . '","original":"' . $file['name'] . '","state":"SUCCESS"}';
		exit;
	}
	
	public function on_setting() {
		$this->_checked['shop_setting'] = 'class="checked"';
		
		$setting = $this->kv->get('shop_setting');
		if(empty($setting)) {
			$setting = array(
				'enable'=>0,
				'alipay'=>array(
					'enable'=>0, 
					'seller_email'=>'', 
					'partner'=>'', 
					'key'=>'', 
					'sign_type'=>'', 
					'input_charset'=>'', 
					'cacert'=>'', 
					'transport'=>'',
					'type'=>1, // 交易类型，1：担保，2：即时到帐
				),
				'tenpay'=>array(
					'enable'=>0,
					'appid'=>0,
					'key'=>0,
				),
				'ebank'=>array(
					'enable'=>0, 
					'mid'=>'', 
					'key'=>'',
				),
				'offline'=>array(
					'enable'=>0, 
					'banklist'=>'',
				)
			);
		}
		
		// 处理 POST 提交
		if(!$this->form_submit()) {
			$input['enable'] = form::get_radio_yes_no('enable', $setting['enable']);
			$input['alipay_enable'] = form::get_radio_yes_no('alipay_enable', $setting['alipay']['enable']);
			$input['alipay_seller_email'] = form::get_text('alipay_seller_email', $setting['alipay']['seller_email'], 300);
			$input['alipay_partner'] = form::get_text('alipay_partner', $setting['alipay']['partner'], 300);
			$input['alipay_key'] = form::get_text('alipay_key', $setting['alipay']['key'], 300);
			$input['alipay_type'] = form::get_radio('alipay_type', array(1=>'担保交易', 2=>'即时到帐'), $setting['alipay']['type']);
			
			// 财付通
			$input['tenpay_enable'] = form::get_radio_yes_no('tenpay_enable', $setting['tenpay']['enable']);
			$input['tenpay_appid'] = form::get_text('tenpay_appid', $setting['tenpay']['appid'], 300);
			$input['tenpay_key'] = form::get_text('tenpay_key', $setting['tenpay']['key'], 300);
			
			//网银支付
			$input['ebank_enable'] = form::get_radio_yes_no('ebank_enable', $setting['ebank']['enable']);
			$input['ebank_mid'] = form::get_text('ebank_mid', $setting['ebank']['mid'], 300);
			$input['ebank_key'] = form::get_text('ebank_key', $setting['ebank']['key'], 300);
			
			// 线下支付，银行列表
			$input['offline_enable'] = form::get_radio_yes_no('offline_enable', $setting['offline']['enable']);
			$input['offline_banklist'] = form::get_textarea('offline_banklist', $setting['offline']['banklist'], 400, 100);
			
			$this->view->assign('input', $input);
			$this->view->display('shop_setting.htm');
		} else {
			$enable = core::gpc('enable', 'P');
			$alipay_enable = core::gpc('alipay_enable', 'P');
			$alipay_seller_email = core::gpc('alipay_seller_email', 'P');
			$alipay_partner = core::gpc('alipay_partner', 'P');
			$alipay_key = core::gpc('alipay_key', 'P');
			$alipay_type = core::gpc('alipay_type', 'P');
			
			$alipay_sign_type = strtoupper('MD5');
			$alipay_input_charset= strtolower('utf-8');
			$alipay_cacert    = $this->conf['plugin_path'].'xn_shop/alipay/cacert.pem';
			$alipay_transport    = 'http';	
			
			if($alipay_enable && !function_exists('curl_init')) {
				$this->message('启用支付宝需要 curl_init() 函数支持，请确定您的 php.ini 启用 curl 模块。', 0);
			}
			
			// 财付通
			$tenpay_enable = core::gpc('tenpay_enable', 'P');
			$tenpay_appid = core::gpc('tenpay_appid', 'P');
			$tenpay_key = core::gpc('tenpay_key', 'P');
			
			// 网银在线
			$ebank_enable = core::gpc('ebank_enable', 'P');
			$ebank_mid = core::gpc('ebank_mid', 'P');
			$ebank_key = core::gpc('ebank_key', 'P');
			
			// 线下支付
			$offline_enable = core::gpc('ebank_enable', 'P');
			$offline_banklist = core::gpc('offline_banklist', 'P');
			
			$setting = array(
				'enable'=>$enable,
				'alipay'=>array(
					'enable'=>$alipay_enable, 
					'seller_email'=>$alipay_seller_email, 
					'partner'=>$alipay_partner, 
					'key'=>$alipay_key, 
					'sign_type'=>$alipay_sign_type, 
					'input_charset'=>$alipay_input_charset, 
					'cacert'=>$alipay_cacert, 
					'transport'=>$alipay_transport,
					'type'=>$alipay_type,
				),
				'tenpay'=>array(
					'enable'=>$tenpay_enable, 
					'appid'=>$tenpay_appid, 
					'key'=>$tenpay_key,
				),
				'ebank'=>array(
					'enable'=>$ebank_enable, 
					'mid'=>$ebank_mid, 
					'key'=>$ebank_key,
				),
				'offline'=>array(
					'enable'=>$offline_enable, 
					'banklist'=>$offline_banklist, // 支持 markdown 语法
				)
			);
			$this->kv->set('shop_setting', $setting);		
			$this->message('设置成功！');
		}
	}
	
	public function on_getremoteimage() {
		$uploadpath = $this->conf['upload_path'].'attach_shop/';
		$uploadurl = $this->conf['upload_url'].'attach_shop/';
		
		$goodid = intval(core::gpc('goodid'));
		$good = $this->shop_good->read($goodid);
		$dateline = empty($good) ? $_SERVER['time'] : $good['dateline'];
		
		$uid = $this->_user['uid'];
		$this->check_forbidden_group();
		$this->check_login();
		
		$url = htmlspecialchars(core::gpc( 'upfile', 'P'));
		$url = str_replace( "&amp;" , "&" , $url);
		$urllist = explode("ue_separate_ue", $url);
		$returnurl = array();
		foreach($urllist as $url) {
			if(empty($url)) {
				//$this->uploaderror('没有URL。');
				$returnurl[] = 'error';
				continue;
			}
			
			preg_match('#/([^/]+)\.(jpg|jpeg|png|gif|bmp)#i', $url, $m);
			if(empty($m[2])) {
				//$this->uploaderror('只支持 jpg, jpeg, png, gif, bmp 格式。');
				$returnurl[] = 'error';
				continue;
			}
			$ext = $m[2];
			$filename = $m[0];
			if(!preg_match('#^(https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*$#is', $url)) {
				//$this->uploaderror('URL 格式不正确。');
				$returnurl[] = 'error';
				continue;
			}
			
			$s = misc::fetch_url($url, 5);
				
			$tmpfile = FRAMEWORK_TMP_TMP_PATH.md5(rand(0, 1000000000).$_SERVER['time'].$_SERVER['ip']).'.'.$ext;
			$succeed = file_put_contents($tmpfile, $s);
			if(!$succeed) {
				//$this->uploaderror('移动临时文件错误，请检查临时目录的可写权限。');
				$returnurl[] = 'error';
				continue;
			}
			
			$diradd = image::get_dir($goodid);
			!is_dir($uploadpath) && mkdir($uploadpath, 0777);
			!is_dir($uploadpath.$diradd) && mkdir($uploadpath.$diradd, 0777);
			if($ext == 'gif') {
				$filepath = $diradd.'/'.$goodid.'_'.rand(0, 1000000000).$_SERVER['time'].'.gif';
				$destfile = $uploadpath.$filepath;
				copy($tmpfile, $destfile);
			} else {
				$filepath = $diradd.'/'.$goodid.'_'.rand(0, 1000000000).$_SERVER['time'].'.'.$ext;
				$destfile = $uploadpath.$filepath;
				image::thumb($tmpfile, $destfile, 1920, 240000);	// 1210 800
			}
			$imginfo = getimagesize($destfile);
			is_file($tmpfile) && unlink($tmpfile);
			$returnurl[] = $uploadurl.$filepath;
			
			
			// db 里记录一下 image
			$arr = array(
				'goodid'=>$goodid,
				'seq'=>0,
				'fileurl'=>$filepath,
				'width'=>$imginfo[0],
				'height'=>$imginfo[1]
			);
			$this->shop_image->create($arr);
		}
		
		// hook admin_shop_remoteimage_end.php
		
		echo '{"url":"' . implode("ue_separate_ue", $returnurl) . '","tip":"远程图片抓取成功！","srcUrl":"' . core::gpc( "upfile", "P") . '","state":"SUCCESS"}';
		exit;
	}
	
	private function check_good_exists($good) {
		if(empty($good)) {
			$this->message('商品不存在。', 0);
		}
	}
	
	private function check_cate_exists($cate) {
		if(empty($cate)) {
			$this->message('分类不存在。', 0);
		}
	}
	
	private function check_order_exists($order) {
		if(empty($order)) {
			$this->message('订单不存在。', 0);
		}
	}
	
	// hook admin_shop_control_after.php
}

?>