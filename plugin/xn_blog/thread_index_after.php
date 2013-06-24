	// 帖子详情页
	// 评论翻页 要显示内容
	foreach($postlist as $key => $val) {
		if($val['pid'] == $thread['firstpid']) {
			break;
		} else {
			$postinfo = $this->post->read($thread['fid'], $thread['firstpid']);
			array_unshift($postlist, $postinfo);	// 在数组头部插入内容
			break;
		}
	}
	
	// 当前板块下 一级主题分类
	$forum = $this->mcache->read('forum', $fid);
	if(!empty($forum) && !empty($forum['types'][1])) {	// 只取一级分类
		$category = $forum['types'][1];
		$typelist = array();
		foreach($category as $catekey => $cateval) {
			$threads = $this->thread_type_count->get_threads($fid, $catekey);
			$typelist[] = array('name' => $cateval, 'num' => $threads, 'typeid' => $catekey, 'fid' => $fid);
		}
	}
	$this->view->assign('typelist', $typelist);
	


	// 评论排行  取最新300条数据 按posts量排序   todo
	$newlist = $this->thread->get_newlist(0, 300);
	if($newlist) {
		foreach($newlist as $pk => $pv) {
			$posts[$pk] = $pv['posts'];	//比较大小的key
		}
		arsort($posts);				// 对数组进行逆向排序并保持索引关系   todo 如果值相同 怎么排序？
		// 排序后  根据key还原数组 
		$plist = array();
		$num = 0;		// 取前10条数据
		foreach ($posts as $postk => $postv){
			$plist[$postk] = $newlist[$postk];
			$plist[$postk]['posts'] = $plist[$postk]['posts'] -1;	// 评论减一
			$plist[$postk]['subject_substr']  = utf8::substr($plist[$postk]['subject'], 0, 15);
			$num++;
			if($num == 11) break;
		}
	}	
	$this->view->assign('plist', $plist);	// 评论排行

	// 上一篇  下一篇  功能 根据fid tid 查询数度很快
	// 博客板块很少，就根据tid的前后值查询，得到结果就是上一篇和下一篇
	// 遍历fid集合 and (tid - 1) = 上一篇 (tid-1 >= 0))
	// 遍历fid集合 and (tid + 1) = 下一篇 (tid+1 <= maxtid)
	$upthread = $nextthread = '';
	if(!empty($this->conf['forumarr'])) {
		for($up = $thread['tid'] - 1; $up > 0; $up--) {		
			foreach($this->conf['forumarr'] as $fk => $fv) {
				if(empty($upthread)) {
					$upthread = $this->thread->read($fk, $up);
					if($upthread) $upthread['subject_substr']  = utf8::substr($upthread['subject'], 0, 20);
				}
				if(!empty($upthread)) break;
			}	
		}
		for($next = $thread['tid'] + 1; $next <= $this->thread->maxid(); $next++) {
			foreach($this->conf['forumarr'] as $fk => $fv) {
				if(empty($nextthread)) {
					$nextthread = $this->thread->read($fk, $next);
					if($nextthread) $nextthread['subject_substr']  = utf8::substr($nextthread['subject'], 0, 20);
				}
				if(!empty($nextthread)) break;
			}	
		}
	}
	$this->view->assign('up', $upthread);
	$this->view->assign('next', $nextthread);

	// 友情链接 先安装友情链接插件
        try {
        	$friendlinklist = array();
		$friendlinklist = $this->friendlink->index_fetch(array('type'=>0), array('rank'=>1), 0, 1000);
		foreach($friendlinklist as &$friendlink) {
			$this->friendlink->format($friendlink);
		}
		$this->view->assign('friendlinklist', $friendlinklist);
        } catch (Exception $e) {
        	$this->message('请安装友情链接插件。');
        }

	// 评论总数 - 帖子总数(每个帖子默认评论1) = 正常评论数
        $pcount = $this->conf['posts'] - $this->conf['threads'];
        $this->view->assign('pcount', $pcount);
	$this->view->assign('postlist', $postlist);
	$this->view->display('plugin_blog_view.htm');
	exit;