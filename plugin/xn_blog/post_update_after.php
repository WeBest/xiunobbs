	// 普通用户修改文章 判断权限
	// xiunobbs已做处理 忽略
	if($user['groupid'] != 1) {
		$this->message("你没有权限修改帖子。", 0);
		exit;
	}