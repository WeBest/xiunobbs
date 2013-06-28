
		$custom_url = core::gpc('custom_url');
		if($custom_url) {
			if(!preg_match('#^[\w\-]+$#', $custom_url)) {
				$this->message('您请求的 URL 传递的参数不合法，自定义 URL 中只能包含英文、数字、下划线、横线。');
			}
			$arrlist = $this->thread->index_fetch(array('custom_url'=>$custom_url), array(), 0, 1);
			if(empty($arrlist)) {
				$this->message('您请求的 URL 不存在。');
			}
			$thread = array_pop($arrlist);
			$_GET['fid'] = $thread['fid'];
			$_GET['tid'] = $thread['tid'];
		}
		