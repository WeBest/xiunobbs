if(!empty($_POST)) {
	$referer = core::gpc('HTTP_REFERER', 'S');
	// 如果不是来自本应用
	$len = substr($this->conf['app_url']);
	if(substr($referer, 0, $len) != $this->conf['app_url']) {
		log::write('检测到非法数据提交，已经被拦截！');
		$this->message('检测到非法数据提交，已经被拦截！', 0);
	}
}