
		$this->_checked['index'] = ' class="checked"';
				
		$pagesize = 50;
		$start = 0;
		
		$threadlist = array();
		$groupid = $this->_user['groupid'];
		if($groupid == 0) {
			$threadlist = $this->runtime->get('index_threadlist');
		}
		if(empty($threadlist) || $groupid == 0) {
			$threadlist = $this->thread->get_newlist($start, $pagesize);
			$unset1 = 0;
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
			
			if($unset1 > 0) {
				$threadlist += (array)$this->thread->get_newlist($start + $pagesize, $pagesize * 2);
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
				$threadlist = array_slice($threadlist, 0, $pagesize);
			}
			if($groupid == 0) {
				$this->runtime->set('index_threadlist', $threadlist, 60);
			}
		}
		
		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('pages', $pages);
		
		// hook index_bbs_after.php
		
		$digestlist = array();
		if($groupid == 0) {
			$digestlist = $this->runtime->get('index_digestlist');
		}
		if(empty($digestlist) || $groupid == 0) {
			$unset2 = 0;
			$digestlist = $this->thread_digest->get_newlist($start, $pagesize);
			foreach($digestlist as $k=>&$thread) {
				$this->thread->format($thread);
				
				// 去掉没有权限访问的版块数据
				$fid = $thread['fid'];
				if(!isset($this->conf['forumarr'][$fid])) {
					unset($digestlist[$k]);
					$unset2++;
					continue;
				}
				$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 32);
			}
			
			if($unset2 > 0) {
				$digestlist += (array)$this->thread_digest->get_newlist($start + $pagesize, $pagesize * 2);
				foreach($digestlist as $k=>&$thread) {
					$this->thread->format($thread);
					// 去掉没有权限访问的版块数据
					$fid = $thread['fid'];
					if(!isset($this->conf['forumarr'][$fid])) {
						unset($digestlist[$k]);
						$unset2++;
						continue;
					}
					$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 32);
				}
				$digestlist = array_slice($digestlist, 0, $pagesize);
			}
			if($groupid == 0) {
				$this->runtime->set('index_digestlist', $digestlist, 60);
			}
		}
		
		$this->view->assign('digestlist', $digestlist);
		$this->view->display('plugin_index_two_column.htm');
		exit;
		
		