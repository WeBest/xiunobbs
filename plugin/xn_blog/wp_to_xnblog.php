<?php

$post = $_POST;
if(empty($post['host']) && empty($post['user']) && empty($post['name']) && empty($post['tablepre'])) {
	echo '配置信息错误， <a href="./index.htm">返回</a>';exit;
}

// 本程序用来升级 wordpress 到 XiunoBlog
	@set_time_limit(0);
	
	define('DEBUG', 0);
	
	define('BBS_PATH', '../../');
	
	// 加载应用的配置文件，唯一的全局变量 $conf
	if(!($conf = include BBS_PATH.'conf/conf.php')) {
		message('配置文件不存在，请先安装 Xiuno BBS。');
	}
	define('FRAMEWORK_PATH', BBS_PATH.'xiunophp/');
	define('FRAMEWORK_TMP_PATH', $conf['tmp_path']);
	define('FRAMEWORK_LOG_PATH', $conf['log_path']);
	include FRAMEWORK_PATH.'core.php';
	core::init();
	core::ob_start();
	
	// runtime
	function get_runtime() {
		$conf = include BBS_PATH.'conf/conf.php';
		$runtime = new runtime($conf);
		return $runtime;
	}
	
	
	// xn2 db instance
	function get_db() {
		$conf = include BBS_PATH.'conf/conf.php';
		$db = new db_mysql($conf['db'][$conf['db']['type']]);
		return $db;
	}
	
	// wp db instance    wp配置文件
	function get_db_wp() {
		$dx2 = new db_mysql(array(
			'master' => array (
				'host' => $_POST['host'],
				'user' => $_POST['user'],
				'password' => $_POST['pass'],
				'name' => $_POST['name'],
				'charset' => 'utf8',	// 要求取出 utf-8 数据 mysql 4.1 以后支持转码
				//'charset' => $_config['db'][1]['dbcharset'],
				'tablepre' => $_POST['tablepre'],
				'engine'=>'MyISAM',
			),
			'slaves' => array ()
		));
		// 要求返回的数据为 utf8
		return $dx2;
	}
	
	// 升级完后 跳转
	function next_step() {
		echo "<p>升级完成，<a href='../../'>查看</a></p>";
	}
	
	// 同步用户
	function upgrade_user() {
		$db = get_db();
		$wp = get_db_wp();
		$runtime = get_runtime();
		// wp 用户
		$wpusers = $wp->index_fetch('users', 'ID', array(), array(), 0, 0);
		if($wpusers) {
			$i = 0;
			$db->set("framework_count-name-user", array('name'=>'user', 'count'=>0));	// 清空数据
			foreach($wpusers as $key => $val) {
				$uid = intval($val['ID']);
				// utf8_general_ci
				if(strpos($val['user_login'], 'ü') !== FALSE || strpos($val['user_login'], 'u') !== FALSE) {
					$srchlist = $db->index_fetch('user', 'uid', array('username' => $val['user_login']), array(), 0, 1);
					if(count($srchlist) > 0) {
						$srchuser = array_pop($srchlist);
						// 对当前用户名改名，改为 uid
						$newname = $val['uid'].rand(1000, 9999);
						$val['username'] = $newname;
						log::write("rename $val[user_login] to $newname");
					}
				}
				
				$salt = rand(100000, 999999);
				$password = md5(md5($val['user_email']).$salt);
				$arr = array (
					'uid'=> $uid,
					'regip'=> '',
					'regdate'=> intval(strtotime($val['user_registered'])),
					'username'=> $val['user_login'],
					'password'=> $password,
					'salt'=> $salt,
					'email'=> $val['user_email'],
					'groupid'=> 11,
					'threads'=> 0,	// todo: 后面统计
					'posts'=> 0,	// todo: 后面统计
					'myposts'=> $myposts,	// todo: 后面统计
					'avatar'=> 0,
					'credits'=> 0,
					'golds'=> 0,
					'follows'=> 0,
					'followeds'=> 0,
					'newpms'=> 0,
					'newfeeds'=> 0,
					'homepage'=> '',
					'accesson'=> 0,
					'onlinetime'=> 0,
					'lastactive'=> 0,
				);
				$db->set("user-uid-$uid", $arr);
				$i++;
				$db->set("framework_count-name-user", array('name'=>'user', 'count'=>$i));	// +1
				$runtime->xset('users', '+1');	// 更新runtime
				$runtime->xsave();
				
				echo 'uid->'.$uid.',  ';
			}
			
			
			// 添加一个新的管理员用户  登录、管理后台用
			$maxuid = $db->index_maxid('user-uid') + 1;
			$db->maxid('user-uid', $maxuid);
			$arr = array (
				'uid'=> $maxuid,
				'regip'=> 0,
				'regdate'=> $_SERVER['time'],
				'username'=> 'admin',
				'password'=> 'd14be7f4d15d16de92b7e34e18d0d0f7',
				'salt'=> '99adde',
				'email'=> 'admin@admin.com',
				'groupid'=> 1,
				'threads'=> 0,
				'posts'=> 0,
				'myposts'=> 0,
				'avatar'=> 0,
				'credits'=> 0,
				'golds'=> 0,
				'follows'=> 0,
				'followeds'=> 0,
				'newpms'=> 0,
				'newfeeds'=> 0,
				'homepage'=> '',
				'accesson'=> 0,
				'lastactive'=> 0,
			);
			$db->set("user-uid-$maxuid", $arr);
			$db->set("framework_count-name-user", array('name'=>'user', 'count'=>($i + 1)));	// +1
			echo 'uid->'.$maxuid.',  ';
		}
	}
	
	// 板块    分类作为板块    todo:标签做为主题分类 20条
	function upgrade_forum() {
		$db = get_db();
		$wp = get_db_wp();
		$runtime = get_runtime();
		// 遍历wp文章表，得到发布文章所有id 
		$threadlist = $wp->index_fetch('posts', 'ID', array('post_status' => 'publish', 'post_type'=>'post'), array(), 0, 0);
		if($threadlist) {
			foreach($threadlist as $threadk => $threadv) {
				// 再查询wp_term_relationships
				$rlist = $wp->index_fetch('term_relationships', array('object_id', 'term_taxonomy_id'), array('object_id' => $threadv['ID']), array(), 0, 0);
				if(!empty($rlist)) {
					// 再查询 wp_term_taxonomy 区分分类 标签
					foreach($rlist as $rt => $rv) {
						$taxonomy = $wp->index_fetch('term_taxonomy', array('term_taxonomy_id'), array('term_taxonomy_id' => $rv['term_taxonomy_id']), array(), 0, 0);
						if(!empty($taxonomy)) {
							$i = 0;
							foreach($taxonomy as $taxt => $taxv) {
								$i++;
								$category = $wp->index_fetch('terms', array('term_id'), array('term_id' => $taxv['term_id']), array(), 0, 0);
								if($taxv['taxonomy'] == 'category') {
									if($category) {
										$category = array_pop($category);
										$category_name = $category['name'];
										$category_id = $category['term_id'];
										if(!$db->get('forum-fid-$category_id')) {
											$forum = array('fid'=>$category_id, 'name'=>$category_name, 'brief'=>$category_name);
											$db->set('forum-fid-$category_id', $forum);
											// 更新最大id
											$fid = $db->maxid('forum-fid');
											if($fid < $category_id) {
												$db->maxid('forum-fid', $category_id);
											}
											echo 'fid->'.$category_id.',  ';
										}
									}
								} else if($taxv['taxonomy'] == 'post_tag') {
									/*    todo 先不处理
									if($category) {
										if($i < 20) {
											$category = array_pop($category);
											$category_type_name = $category['name'];	// 标签做为主题分类
											$category_type_id = $category['term_id'];	// 相当于fid
											$thread_type = array('fid'=>$category_type_id, 'typeid'=>$i, 'typename'=>$category_type_name, 'rank'=>$i, 'enable'=>1);
											$db->set('thread_type-fid-$category_id-typeid-$i', $thread_type);
										}
									}
									*/
								} else {
									// 忽略
								}
							}
						}
					}
				}
			}
			// 更新板块
			$runtime->xupdate('forumarr');
			$runtime->xsave();
			// 更新fourm count
			$fcount = $db->index_fetch('forum', 'fid', array(), array(), 0, 0);
			$db->count('forum', count($fcount));								
		}
	}
	
	// 文章导入
	function upgrade_thread() {
		$db = get_db();
		$wp = get_db_wp();
		$runtime = get_runtime();
		// 遍历wp文章表，得到发布文章所有id 
		$threadlist = $wp->index_fetch('posts', 'ID', array('post_status' => 'publish', 'post_type'=>'post'), array(), 0, 0);
		if($threadlist) {
			foreach($threadlist as $threadk => $threadv) {
				// 发帖人用户信息
				$userinfo = $wp->index_fetch('users', 'ID', array('ID' =>$threadv['post_author']), array(), 0, 0);	// 用户信息
				$userinfo = array_pop($userinfo);
				
				// 文字找到分类 id
				$rlist = $wp->index_fetch('term_relationships', array('object_id', 'term_taxonomy_id'), array('object_id' => $threadv['ID']), array(), 0, 0);
				if($rlist) {
					foreach ($rlist as $rk => $rv) {
						// 再查询 wp_term_taxonomy 区分分类 标签
						$taxonomy = $wp->index_fetch('term_taxonomy', array('term_taxonomy_id'), array('term_taxonomy_id' => $rv['term_taxonomy_id']), array(), 0, 0);
						if(!empty($taxonomy)) {
							$taxonomy = array_pop($taxonomy);
							if($taxonomy['taxonomy'] == 'category') {
								// thread
								$fid = $taxonomy['term_id'];
								$thread = array(
									'fid'=>$fid,
									'uid'=>$threadv['post_author'],
									'username'=>$userinfo['user_login'],
									'subject'=>$threadv['post_title'],
									'dateline'=>strtotime($threadv['post_date']),
									'lastpost'=>strtotime($threadv['post_date']),
									'lastuid'=>'','lastusername'=>'','views'=>0,'posts'=>1,'top'=>0,			'imagenum'=>0,	// 需要最后更新
									'attachnum'=>0, 'modnum'=>0, 'closed'=>0,
									'firstpid'=>0,	// 需要最后更新，也就是最小的pid，冗余存储，提高速度
									'typeid1'=>0, 'typeid2'=>0,'typeid3'=>0, 'typeid4'=>0, 'status'=>0,
								);
								$tid = $db->maxid('thread-tid') + 1;
								$db->maxid('thread-tid', $tid);
								$db->set("thread-fid-$fid-tid-$tid", $thread);
								$db->set("thread_views-tid-$tid", array('tid'=>$tid, 'views'=>0));  // 创建浏览次数
								$db->set("thread_new-tid-$tid", array('fid'=>$fid, 'tid'=>$tid, 'dateline'=>strtotime($threadv['post_date']), 'lastpost'=>strtotime($threadv['post_date'])));
								$newcount = $db->count('thread_new');
								$db->count('thread_new', $newcount + 1);
								$runtime->xset('threads', '+1');	// 更新runtime
								$runtime->xsave();
								echo 'tid->'.$tid.',  ';
								
								// 更新 count
								$count = $db->count('thread');
								$db->count('thread', $count + 1);
								
								// post
								$post = array (
									'fid'=>$fid,
									'tid'=>$tid,
									'uid'=>$threadv['post_author'],
									'username'=>$userinfo['user_login'],
									'dateline'=>strtotime($threadv['post_date']),
									'userip'=>0,'attachnum'=>0,'imagenum'=>0,'rates'=>0,'page'=>1,'subject'=>'',
									'message'=>$threadv['post_content'],
								);
								$pid = $db->maxid('post-pid') + 1;
								$db->maxid('post-pid', $pid);
								$db->set("post-fid-$fid-pid-$pid", $post);
								$pcount = $db->count('post');
								$db->count('post', $pcount +1);
								$runtime->xset('posts', '+1');
								$runtime->xsave();
								
								$threadinfo = $db->get("thread-fid-$fid-tid-$tid");
								$threadinfo['firstpid'] = $pid;
								$db->set("thread-fid-$fid-tid-$tid", $threadinfo);
								
								$foruminfo = $db->get("forum-fid-$fid");
								$foruminfo['threads'] += 1;	// 文章数+1
								$db->set("forum-fid-$fid", $foruminfo);
								
								/*
								// 附件处理    todo
								if(!empty($post['message'])) {
									preg_match_all("#\<img(.*?)\>#is", $post['message'], $match);		// 正则文章中的所有图片 <img src=.....
									if(empty($match)) continue;
									
								}
								*/
								
								
								// 查看该文章是否有评论  comment_approved 审核状态
								$replylist = $wp->index_fetch('comments', array('comment_ID'), array('comment_post_ID' => $threadv['ID'], 'comment_approved'=>1), array(), 0, 0);
								if($replylist) {
									foreach ($replylist as $repk => $repv) {
										// 根据 评论信息 先查询数据库是否有账号， 没有就注册帐号
										if($repv['comment_author_email']) {
											$userinfo = $db->index_fetch('user', 'uid', array('email' =>$repv['comment_author_email']), array(), 0, 0);	// 用户信息	
											if(empty($userinfo)) {
												// 根据email注册用户
												$uid = $db->maxid('user-uid') + 1;
												$db->maxid('user-uid', $uid);
												$salt = rand(100000, 999999);
												$password = md5(md5($repv['comment_author_email']).$salt);
												$userinfo = array (
													'uid'=> $uid,
													'regip'=> 0,
													'regdate'=> strtotime($repv['comment_date']),
													'username'=> $repv['comment_author'] == '' ? $repv['comment_author_email'] : $repv['comment_author'],
													'password'=> $password,
													'salt'=> $salt,
													'email'=> $repv['comment_author_email'],
													'groupid'=> 11,
													'threads'=> 0,
													'posts'=> 0,
													'myposts'=> 0,
													'avatar'=> 0,
													'credits'=> 0,
													'golds'=> 0,
													'follows'=> 0,
													'followeds'=> 0,
													'newpms'=> 0,
													'newfeeds'=> 0,
													'homepage'=> '',
													'accesson'=> 0,
													'lastactive'=> 0,
												);
												$db->set("user-uid-$uid", $userinfo);
												$ucount = $db->count('user');
												$db->count('user', $ucount +1);
												$userinfo['uid'] = $uid;
											} else {
												$userinfo = array_pop($userinfo);
											}
										} else {
											// 随机生存用户信息  邮箱为空
											$uid = $db->maxid('user-uid') + 1;
											$db->maxid('user-uid', $uid);
											$salt = rand(100000, 999999);
											$rand = time().$salt;
											$email = $rand.'@qq.com';
											$password = md5(md5($email).$salt);
											$userinfo = array (
												'uid'=> $uid,
												'regip'=> 0,
												'regdate'=> time(),
												'username'=> $rand,
												'password'=> $password,
												'salt'=> $salt,
												'email'=> $email,
												'groupid'=> 11,
												'threads'=> 0,
												'posts'=> 0,
												'myposts'=> 0,
												'avatar'=> 0,
												'credits'=> 0,
												'golds'=> 0,
												'follows'=> 0,
												'followeds'=> 0,
												'newpms'=> 0,
												'newfeeds'=> 0,
												'homepage'=> '',
												'accesson'=> 0,
												'lastactive'=> 0,
											);
											$db->set("user-uid-$uid", $userinfo);
											$ucount = $db->count('user');
											$db->count('user', $ucount +1);
											$userinfo['uid'] = $uid;
										}
										
										// post   全部是一级评论了
										$post = array (
											'fid'=>$fid,
											'tid'=>$tid,
											'uid'=>$userinfo['uid'],
											'username'=>$userinfo['username'],
											'dateline'=>strtotime($repv['comment_date']),
											'userip'=>ip2long($repv['comment_author_IP']),'attachnum'=>0,'imagenum'=>0,'rates'=>0,'page'=>1,'subject'=>'',
											'message'=>$repv['comment_content'],
										);
										$pid = $db->maxid('post-pid') + 1;
										$db->maxid('post-pid', $pid);
										$db->set("post-fid-$fid-pid-$pid", $post);
										$pcount = $db->count('post');
										$db->count('post', $pcount +1);
										$runtime->xset('posts', '+1');
										$runtime->xsave();
										echo '评论id->'.$pid.',  ';
										
										// 当前帖子评论数 +1
										$threadinfo = $db->get("thread-fid-$fid-tid-$tid");
										$threadinfo['posts'] += 1;
										$db->set("thread-fid-$fid-tid-$tid", $threadinfo);
									}
								}
								
								
								// mypost
								$db->set("mypost-tid-$tid", array('uid'=>$threadv['post_author'], 'fid'=>$fid, 'tid'=>$tid, 'pid'=>$pid));
								
								// thread_blog
								$message = htmlspecialchars(utf8::substr(strip_tags($threadv['post_content']), 0, 140));
								$thread_blog = array('fid'=>$fid, 'tid'=>$tid, 'coverimg'=>'', 'brief'=>$message);
								$db->set("thread_blog-fid-$fid-tid-$tid", $thread_blog);
								
								break;	// 一篇文章就一个分类
								
							}
						}
					}
				}
			}
		}
	}
	
	function upgrade_link() {
		if(class_exists('friendlink')) {
			$db = get_db();
			$wp = get_db_wp();
			
			$link_list = $wp->index_fetch('links', 'link_id', array(), array(), 0, 0);	// 友情连接
			if($link_list) {
				foreach ($link_list as $lk => $lv) {
					$link_id = $db->maxid('friendlink-linkid') + 1;
					$db->maxid('friendlink-linkid', $link_id);
					$link_arr = array('linkid'=>$link_id, 'rank'=>$link_id, 'sitename'=>$lv['link_name'], 'url'=>$lv['link_url']);
					$db->set("friendlink-linkid-$link_id", $link_arr);
					$fcount = $db->count('friendlink');
					$db->count('friendlink', $fcount +1);
				}
			}
		}
		next_step();
	}
	
	
	
	upgrade_user();
	upgrade_forum();
	upgrade_thread();
	upgrade_link();
	
?>