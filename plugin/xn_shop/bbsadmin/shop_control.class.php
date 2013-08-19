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
	}
	
	// 创建分类
	public function on_createcate() {
		
	}
	
}

?>