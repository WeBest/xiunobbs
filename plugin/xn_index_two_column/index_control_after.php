
	// 最新
	public function on_new() {
	
		$this->_checked['index'] = ' class="checked"';
		
		$pagesize = $this->conf['forum_index_pagesize'];
		$page = misc::page();
		$start = ($page - 1) * $pagesize;
		
		$threadlist = array();
		$readtids = '';
			
		$threadlist = $this->thread->get_newlist($start, $pagesize);
		$n = count($threadlist);
		foreach($threadlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			if(!isset($this->conf['forumarr'][$fid])) {
				unset($threadlist[$k]);
				continue;
			}
			$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 32);
			$readtids .= ','.$thread['tid'];
		}
		
		$readtids = substr($readtids, 1); 
		$click_server = $this->conf['click_server']."?db=tid&r=$readtids";
		
		$pages = misc::simple_pages("?index-new.htm", $n, $page, $pagesize);

		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $threadlist);
		$this->view->assign('pages', $pages);
		$this->view->assign('click_server', $click_server);
		$this->view->display('plugin_index_new.htm');
	}
	
	// 精华
	public function on_digest() {
	
		$this->_checked['index'] = ' class="checked"';
		
		$pagesize = $this->conf['forum_index_pagesize'];
		$page = misc::page();
		$start = ($page - 1) * $pagesize;
		
		$readtids = '';
		
		$digestlist = $this->thread_digest->get_newlist($start, $pagesize);
		$n = count($digestlist);
		
		foreach($digestlist as $k=>&$thread) {
			$this->thread->format($thread);
			
			// 去掉没有权限访问的版块数据
			$fid = $thread['fid'];
			if(!isset($this->conf['forumarr'][$fid])) {
				unset($digestlist[$k]);
				continue;
			}
			$thread['subject_fmt'] = utf8::substr($thread['subject'], 0, 18);
			$readtids .= ','.$thread['tid'];
		}
		
		$readtids = substr($readtids, 1); 
		$click_server = $this->conf['click_server']."?db=tid&r=$readtids";
		
		$pages = misc::simple_pages("?index-digest.htm", $n, $page, $pagesize);

		// 在线会员
		$ismod = ($this->_user['groupid'] > 0 && $this->_user['groupid'] <= 4);
		$fid = 0;
		$this->view->assign('ismod', $ismod);
		$this->view->assign('fid', $fid);
		$this->view->assign('threadlist', $digestlist);
		$this->view->assign('pages', $pages);
		$this->view->assign('click_server', $click_server);
		$this->view->display('plugin_index_digest.htm');
	}
	
	private function cutstr_cn($s, $len) {
		$n = strlen($s);
		$r = '';
		$rlen = 0;
		
		// 32, 64
		$UTF8_1 = 0x80;
		$UTF8_2 = 0x40;
		$UTF8_3 = 0x20;
		
		for($i=0; $i<$n; $i++) {
			$c = '';
			$ord = ord($s[$i]);
			if($ord < 127) {
				$rlen++;
				$r .= $s[$i];
			} elseif(($ord & $UTF8_1)  && ($ord & $UTF8_2) && ($ord & $UTF8_3)) {
				// 期望后面的字符满足条件,否则抛弃	  && ord($s[$i+1]) & $UTF8_2
				if($i+1 < $n && (ord($s[$i+1]) & $UTF8_1)) {
					if($i+2 < $n && (ord($s[$i+2]) & $UTF8_1)) {
						$rlen += 2;
						$r .= $s[$i].$s[$i+1].$s[$i+2];
					} else {
						$i += 2;
					}
				} else {
					$i++;
				}
			}
			if($rlen >= $len) break;
		}
		return $r;
	}
	