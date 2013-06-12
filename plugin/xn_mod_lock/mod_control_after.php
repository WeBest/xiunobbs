
	// b 中位为1，设置对应的 a 的位为0
	private function bit_set_zero($a, $b) {
		$a = ~$a;
		$a |= $b;
		$a = ~$a;
		return $a;
	}
	
	public function on_lock() {
		$this->_title[] = '锁住主题';
		$this->_nav[] = '锁住主题';
		
		$this->check_login();
		
		// 锁定的状态
		define('XN_LOCK_STATUS_POST', 1);
		define('XN_LOCK_STATUS_EDIT', 2);
		define('XN_LOCK_STATUS_TYPE', 4);
		define('XN_LOCK_STATUS_ATTACH', 8);
		
		$fid = intval(core::gpc('fid'));
		$tidarr = $this->get_tidarr();
		
		$forum = $this->forum->read($fid);
		$this->check_forum_exists($forum);
		
		$this->check_access($forum, 'lock');
		
		if(!$this->form_submit()) {
			
			// 第一个元素作为选中状态
			$fid_tid = array_shift($tidarr);
			list($fid, $tid) = explode('-', $fid_tid);
			$thread = $this->thread->read($fid, $tid);
			$this->check_thread_exists($thread);
			
			$input = array();
			$input['lockpost'] = form::get_checkbox_yes_no('lockpost', ($thread['closed'] & XN_LOCK_STATUS_POST) ? 1 : 0);
			$input['lockedit'] = form::get_checkbox_yes_no('lockedit', ($thread['closed'] & XN_LOCK_STATUS_EDIT) ? 1 : 0);
			$input['locktype'] = form::get_checkbox_yes_no('locktype', ($thread['closed'] & XN_LOCK_STATUS_TYPE) ? 1 : 0);
			$input['lockattach'] = form::get_checkbox_yes_no('lockattach', ($thread['closed'] & XN_LOCK_STATUS_ATTACH) ? 1 : 0);
			$this->view->assign('input', $input);
			$this->view->assign('thread', $thread);
			$this->view->assign('fid', $fid);
			$this->view->assign('tid', $tid);
			
			// hook mod_lock_before.php
			$this->view->display('mod_lock_ajax.htm');
		} else {
			$lockpost = intval(core::gpc('lockpost', 'P'));
			$lockedit = intval(core::gpc('lockedit', 'P'));
			$locktype = intval(core::gpc('locktype', 'P'));
			$lockattach = intval(core::gpc('lockattach', 'P'));
			$systempm = intval(core::gpc('systempm', 'P'));
			
			$comment = core::gpc('comment', 'P');
			$this->check_comment($comment);
			
			$fidarr = $lockarr = array();
			
			// hook mod_lock_after.php
			foreach($tidarr as &$v) {			// 此处也得用 &
				// 初始化数据
				list($fid, $tid) = explode('-', $v);
				$fid = intval($fid);
				$tid = intval($tid);
				$thread = $this->thread->read($fid, $tid);
				if(empty($thread)) continue;
					
				$thread['closed'] = $lockpost ? ($thread['closed'] |= XN_LOCK_STATUS_POST) : $this->bit_set_zero($thread['closed'], XN_LOCK_STATUS_POST);
				$thread['closed'] = $lockedit ? ($thread['closed'] |= XN_LOCK_STATUS_EDIT) : $this->bit_set_zero($thread['closed'], XN_LOCK_STATUS_EDIT);
				$thread['closed'] = $locktype ? ($thread['closed'] |= XN_LOCK_STATUS_TYPE) : $this->bit_set_zero($thread['closed'], XN_LOCK_STATUS_TYPE);
				$thread['closed'] = $lockattach ? ($thread['closed'] |= XN_LOCK_STATUS_ATTACH) : $this->bit_set_zero($thread['closed'], XN_LOCK_STATUS_ATTACH);
				$this->thread->update($thread);
				
				// 记录到版主操作日志
				$this->modlog->create(array(
					'uid'=>$this->_user['uid'],
					'username'=>$this->_user['username'],
					'fid'=>$fid,
					'tid'=>$tid,
					'pid'=>0,
					'subject'=>$thread['subject'],
					'credits'=> 0,
					'golds'=> 0,
					'dateline'=>$_SERVER['time'],
					'action'=>$thread['closed'] == 0 ? 'unlock' : 'lock',
					'comment'=>$comment,
				));
				
				$this->inc_modnum($fid, $tid);
				
				// 发送系统消息：
				if($systempm) {
					$pmsubject = utf8::substr($thread['subject'], 0, 32);
					$pmmessage = "您的主题<a href=\"?thread-index-fid-$fid-tid-$tid.htm\" target=\"_blank\">【{$pmsubject}】</a>被【{$this->_user['username']}】".($thread['closed'] > 0 ? '加锁' : '解锁')."。";
					$this->pm->system_send($thread['uid'], $thread['username'], $pmmessage);
				}
				
				// hook mod_lock_loop_after.php
			}
			
			// hook mod_lock_succeed.php
			$this->message('操作成功！');
		}
	}
	