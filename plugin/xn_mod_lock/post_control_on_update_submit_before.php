		// 锁定的状态
		define('XN_LOCK_STATUS_POST', 1);
		define('XN_LOCK_STATUS_EDIT', 2);
		define('XN_LOCK_STATUS_TYPE', 4);
		define('XN_LOCK_STATUS_ATTACH', 8);
		
		$ismod = $this->is_mod($forum, $this->_user);
		if(!$ismod && $thread['closed'] & XN_LOCK_STATUS_EDIT) {
			$this->message('该主题已经被锁住（编辑），不能编辑。', 0);
		}