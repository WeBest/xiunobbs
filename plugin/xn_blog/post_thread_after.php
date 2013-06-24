	// 用户提交文章 判断权限
	if($user['groupid'] != 1) {
		$this->message("你没有权限发帖。", 0);
		exit;
	}