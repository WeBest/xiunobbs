	$this->thread_blog = core::model($this->conf, 'thread_blog', array('fid', 'tid'));	// 实例化thread_blog model
	$pagesize = 10;
	$toplist = array();
	$readtids = '';
	$page = misc::page();
	$start = ($page -1 ) * $pagesize;
	$threadlist = $this->thread->get_newlist($start, $pagesize);	// 最新三天数据 更多分板块看信息
	foreach($threadlist as $k=>&$thread) {
		$forum = $this->mcache->read('forum', $thread['fid']);
		$this->thread->format($thread, $forum);
		
		// 附件aid 简介
		$threadblog = $this->thread_blog->read($thread['fid'], $thread['tid']);
		if(!empty($threadblog)) {
			$thread['coverimg'] = $threadblog['coverimg'];
			$thread['brief'] = $threadblog['brief'];
		}

		$readtids .= ','.$thread['tid'];
	}
	$totalnum = $this->thread->count();
	// 翻页有可能为空 查看更多分板块
        $pages = misc::pages('?index-index.htm', $totalnum, $page, $pagesize);
        
        // 获取文章的点击量
        $readtids = substr($readtids, 1); 
	$click_server = $this->conf['click_server']."?db=tid&r=$readtids";
        
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
        
        /*  首页不显示分类信息
	// 分类信息 每个板块下 只查询一级分类   权限没有检测
	if(!empty($this->conf['forumarr'])) {
		$category = array();
		foreach($this->conf['forumarr'] as $k => $v) {
			$cate = $this->mcache->read('forum', $k);
			if(!empty($cate) && !empty($cate['types'][1])) {	// 只取一级分类
				$category[$cate['fid']] = $cate['types'][1];
			}
		}
		$typelist = array();
		foreach($category as $catekey => $cateval) {
			foreach($cateval as $typekey => $typeval) {
				$threads = $this->thread_type_count->get_threads($catekey, $typekey);
				$typelist[] = array('name' => $typeval, 'num' => $threads, 'typeid' => $typekey, 'fid' => $catekey);
			}
		}
	}
	$this->view->assign('typelist', $typelist);
	*/

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
	$pcount = $this->conf['posts'] - $this->conf['threads'];	// 评论总数 - 帖子总数 = 正常评论数
	$this->view->assign('pcount', $pcount);
	$this->view->assign('threadlist', $threadlist);
	$this->view->assign('pages', $pages);
	$this->view->assign('click_server', $click_server);
	$this->view->display('plugin_blog_index.htm');
	exit;