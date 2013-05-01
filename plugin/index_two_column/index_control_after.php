
	// 最新
	public function on_new() {
	
		$this->_checked['index'] = ' class="checked"';
		
		$pagesize = $this->conf['forum_index_pagesize'];
		$page = misc::page();
		$start = ($page - 1) * $pagesize;
		
		$threadlist = array();
			
		$threadlist = $this->thread->get_newlist($start, $pagesize);
		$n = count($threadlist);
		foreach($threadlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			if(!isset($this->conf['forumarr'][$fid])) {
				unset($threadlist[$k]);
				$unset1++;
				continue;
			}
			$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 32);
		}
		
		$pages = misc::simple_pages("?index-new.htm", $n, $page, $pagesize);

		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('pages', $pages);
		$this->view->display('plugin_index_new.htm');
	}
	
	// 精华
	public function on_digest() {
	
		$this->_checked['index'] = ' class="checked"';
		
		$pagesize = $this->conf['forum_index_pagesize'];
		$page = misc::page();
		$start = ($page - 1) * $pagesize;
		
		$digestlist = $this->thread_digest->get_newlist($start, $pagesize);
		$n = count($digestlist);
		foreach($digestlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			if(!isset($this->conf['forumarr'][$fid])) {
				unset($digestlist[$k]);
				$unset1++;
				continue;
			}
			$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 32);
		}
		
		$pages = misc::simple_pages("?index-digest.htm", $n, $page, $pagesize);

		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $digestlist);
		$this->view->assign('pages', $pages);
		$this->view->display('plugin_index_digest.htm');
	}
	