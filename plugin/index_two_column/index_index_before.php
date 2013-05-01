		$this->_checked['index'] = ' class="checked"';
		
		$pagesize = 50;
		$page = misc::page();
		$page2 = misc::page('page2');
		$start = ($page - 1) * $pagesize;
		
		$threadlist = array();
		if($page == 1) {
			$threadlist = $this->runtime->get('threadlist');
		}
		if(empty($threadlist) || $page != 1) {
			
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
			if($page == 1) {
				$this->runtime->set('threadlist', $threadlist, 60);
			}
		}
		
		
		$pages = misc::simple_pages("?index-index-page2-$page2.htm", count($threadlist), $page, $pagesize, 'page');

		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('toplist', $toplist);
		$this->view->assign('pages', $pages);
		
		// hook index_bbs_after.php
		
		$digestlist = array();
		if($page == 1) {
			$digestlist = $this->runtime->get('digestlist');
		}
		if(empty($digestlist) || $page != 1) {
			$unset2 = 0;
			$start = ($page2 - 1) * $pagesize;
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
			if($page == 1) {
				$this->runtime->set('digestlist', $digestlist, 60);
			}
		}
		
		
		
		$pages2 = misc::simple_pages("?index-index-page-$page.htm", count($digestlist), $page2, $pagesize, 'page2');
		$this->view->assign('digestlist', $digestlist);
		$this->view->assign('pages2', $pages2);
		$this->view->assign('click_server', $click_server);
		$this->view->display('plugin_index_two_column.htm');
		exit;
		
		