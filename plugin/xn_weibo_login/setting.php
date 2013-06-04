<?php

$error = $input = array();

if(!$this->form_submit()) {
	$weibologin = $this->kv->get('weibologin');
	
	!isset($weibologin['enable']) && $weibologin['enable'] = 0;
	!isset($weibologin['meta']) && $weibologin['meta'] = '';
	!isset($weibologin['appkey']) && $weibologin['appkey'] = '';
	!isset($weibologin['appsecret']) && $weibologin['appsecret'] = '';
	$input['enable'] = form::get_radio_yes_no('enable', $weibologin['enable']);
	$input['meta'] = form::get_text('meta', htmlspecialchars($weibologin['meta']), 300);
	$input['appsecret'] = form::get_text('appsecret', $weibologin['appsecret'], 300);
	$input['appkey'] = form::get_text('appkey', $weibologin['appkey'], 300);
	$this->view->assign('dir', $dir);
	$this->view->assign('input', $input);
	$this->view->display('plugin_xn_weibo_login.htm');
} else {
	
	$enable = core::gpc('enable', 'R');
	$meta = core::gpc('meta', 'R');
	$appsecret = core::gpc('appsecret', 'R');
	$appkey = core::gpc('appkey', 'R');
	
	if($meta) {
		if(!preg_match('#<meta[^<>]+/>#is', $meta)) {
			$this->message('meta标签格式不正确！');
		}
		$file = BBS_PATH.'plugin/xn_weibo_login/header_css_before.htm';
		if(!file_put_contents($file, $meta)) {
			$this->message('写入文件 plugin/xn_weibo_login/header_css_before.htm 失败，请检查文件是否可写<br />或者手工编辑此文件内容为：'.htmlspecialchars($meta));
		}
		// 删除 tmp 下的缓存文件
		misc::rmdir($this->conf['tmp_path'], 1);
	}
	
	$this->kv->set('weibologin', array('enable'=>$enable, 'meta'=>$meta, 'appsecret'=>$appsecret, 'appkey'=>$appkey));
	$this->kv->xset('weibologin_enable', $enable);
	$this->runtime->xset('weibologin_enable', $enable);
	
	// 如果是 mysql 新建表
	
	$this->message('设置成功！', 1, $this->url('plugin-setting-dir-xn_weibo_login.htm'));
	
}

?>
