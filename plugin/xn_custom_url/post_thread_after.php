if($this->_user['groupid'] <= 5) {
	$custom_url = substr(htmlspecialchars(core::gpc('custom_url', 'R')), 0, 64);
	
	if($custom_url) {
		// 检查格式
		if(!preg_match('#^[\w\-]+$#', $custom_url)) {
			$error['custom_url'] = '自定义 URL 中只能包含英文、数字、下划线、横线。';
		}
		
		// 检查重复
		if(empty($error['custom_url'])) {
			$arrlist = $this->thread->index_fetch(array('custom_url'=>$custom_url), array(), 0, 1);
			if(!empty($arrlist)) {
				$error['custom_url'] = '该 URL 已经被使用，请换其他 URL 试试。';
			}
		}
	}

} else {
	$custom_url = '';
}

$thread['custom_url'] = $custom_url;