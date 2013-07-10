		// 锁定的状态
		define('XN_LOCK_STATUS_POST', 1);
		define('XN_LOCK_STATUS_EDIT', 2);
		define('XN_LOCK_STATUS_TYPE', 4);
		define('XN_LOCK_STATUS_ATTACH', 8);
		
		if($pid > 0) {
			$ismod = $this->is_mod($forum, $this->_user);
			$post = $this->post->read($fid, $pid);
			$this->check_post_exists($post);
			$tid = $post['tid'];
			$thread = $this->thread->read($fid, $tid);
			$this->check_thread_exists($thread);
			if(!$ismod && $thread['closed'] & XN_LOCK_STATUS_ATTACH) {
				$this->message('该主题已经被锁住（上传附件），不能上传附件。', 0);
			}
		}