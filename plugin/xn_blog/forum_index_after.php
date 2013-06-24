	$this->thread_blog = core::model($this->conf, 'thread_blog', array('fid', 'tid'));
	
	if(!empty($threadlist)) {
		foreach($threadlist as $k=>&$thread) {
			$forum = $this->mcache->read('forum', $thread['fid']);
			$this->thread->format($thread, $forum);
			// 查询封面 简介信息
			$threadblog = $this->thread_blog->read($thread['fid'], $thread['tid']);
			$thread['coverimg'] = $threadblog['coverimg'];
			$thread['brief'] = $threadblog['brief'];
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

	// 评论总数 - 帖子总数 = 正常评论数
	$pcount = $this->conf['posts'] - $this->conf['threads'];
	$this->view->assign('pcount', $pcount);

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
        
        // 评论排行  取最新300条数据 按posts量排序   todo
	$postlist = $this->thread->get_newlist(0, 300);
	if($postlist) {
		foreach($postlist as $pk => $pv) {
			$posts[$pk] = $pv['posts'];	//比较大小的key
		}
		arsort($posts);				// 对数组进行逆向排序并保持索引关系   todo 如果值相同 怎么排序？
		// 排序后  根据key还原数组 
		$plist = array();
		$num = 0;		// 取前10条数据
		foreach ($posts as $postk => $postv){
			$plist[$postk] = $postlist[$postk];
			$plist[$postk]['posts'] = $plist[$postk]['posts'] -1;	// 评论减一
			$plist[$postk]['subject_substr']  = utf8::substr($plist[$postk]['subject'], 0, 15);
			$num++;
			if($num == 11) {
				break;
			}
		}
	}
	$this->view->assign('plist', $plist);	// 评论排行
	$this->view->display('plugin_blog_index.htm');
	exit;