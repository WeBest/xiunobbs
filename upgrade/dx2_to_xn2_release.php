<?php

/*
 * Copyright (C) xiuno.com
 */

// 本程序用来升级 DiscuzX 2.0 到 Xiuno BBS 2.0.0 Release，支持重复升级，断点升级。
/*
	流程：
		1. 备份原站点：新建目录: dx2, 将所有文件移动到 dx2 中
		2. 上传 XiunoBBS 2.0.0 源代码，通过 url 安装，安装成功以后进入第3步。
		3. 访问 http://www.domain.com/dx2_to_xn2.php 开始升级
		4. 升级完毕后，删除升级目录 upgrade!!!
*/


/*
	uid = 1 默认为管理员
	system uid 设置到 conf/conf.php
	转换置顶帖，精华帖。
*/

// 积分范围确定用户组
// 转换那些积分 extcredits

@set_time_limit(0);

define('DEBUG', 0);

define('BBS_PATH', './');

// DX2_PATH 需要配置正确！
define('DX2_PATH', BBS_PATH.'dx2/');

// 以下为默认路径，一般情况不需要修改！
define('DX2_CONF_FILE', DX2_PATH.'config/config_global.php');
define('UCENTER_CONF_FILE', DX2_PATH.'config/config_ucenter.php');
define('DX2_ATTACH_PATH', DX2_PATH.'data/attachment/');
define('DX2_AVATAR_PATH', DX2_PATH.'uc_server/data/avatar/');

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

// 初始化参数
loading_upgrade_process($step, $start, $start2);
$step = isset($_GET['step']) ? $_GET['step'] : $step;
$start = isset($_GET['start']) ? intval($_GET['start']) : $start;
$start2 = isset($_GET['start2']) ? intval($_GET['start2']) : $start2;

// 输入 dx2 路径！ 检查 xn2 安装。取得 max uid


// 升级配置文件
if(empty($step)) {
	// 如果没有升级进度，则清空
	$file = $conf['tmp_path'].'upgrade_process.txt';
	if(!is_file($file)) {
		$db = get_db();
		$db->truncate('forum');
		$db->truncate('thread');
		$db->truncate('post');
		$db->truncate('user');
		$db->truncate('mypost');
		$db->truncate('thread_type');
		$db->truncate('friendlink');
		$db->truncate('kv');
		$db->truncate('runtime');
		
		// 用户相关资料
		$db->query("CREATE TABLE IF NOT EXISTS {$db->tablepre}user_ext (
	  uid int(11) unsigned NOT NULL default '0',	
	  gender tinyint(11) unsigned NOT NULL default '0',	
	  birthyear int(11) unsigned NOT NULL default '0',	
	  birthmonth int(11) unsigned NOT NULL default '0',	
	  birthday int(11) unsigned NOT NULL default '0',	
	  province char(16) NOT NULL default '',
	  city char(16) NOT NULL default '',
	  county char(16) NOT NULL default '',
	  KEY (birthyear, birthmonth),
	  KEY (province),
	  KEY (city),
	  KEY (county),
	  PRIMARY KEY (uid));");
	}
	upgrade_conf();
} elseif($step == 'upgrade_prepare') {
	upgrade_prepare();
} elseif($step == 'upgrade_forum') {
	upgrade_forum();
} elseif($step == 'upgrade_thread') {
	upgrade_thread();
} elseif($step == 'upgrade_thread_type') {
	upgrade_thread_type();
} elseif($step == 'upgrade_post') {
	upgrade_post();
} elseif($step == 'upgrade_attach') {
	upgrade_attach();
} elseif($step == 'upgrade_user') {
	upgrade_user();
} elseif($step == 'upgrade_pm') {
	upgrade_pm();
} elseif($step == 'upgrade_friendlink') {
	upgrade_friendlink();
} elseif($step == 'upgrade_mod') {
	upgrade_mod();
} elseif($step == 'upgrade_stat') {
	upgrade_stat();
} elseif($step == 'upgrade_postpage') {
	upgrade_postpage();
} elseif($step == 'upgrade_forum2') {
	upgrade_forum2();
} elseif($step == 'laststep') {
	laststep();
}

function upgrade_conf() {
	
	global $conf;
	
	//global $old, $conf;
	$dx2 = get_dx2();
	
	// 各种检查
	$dx2_path = DX2_PATH;
	if(!is_dir($dx2_path)) {
		message("路径: $dx2_path 不存在，请将 Discuz!X 2.0 所有文件目录移动到 dx2 目录下。");
	}
	
	// 取老的 setting
	$settinglist = $dx2->index_fetch('common_setting', 'skey', array(), array(), 0, 1000);
	$old = misc::arrlist_key_values($settinglist, 'skey', 'svalue');
	
	// 写入配置文件，仅支持mysql
	$kv = core::model($conf, 'kv');
	$kv->xset('app_name', $old['bbname']);
	$kv->xsave();
	
	message('修改配置文件成功，接下来一些准备工作...', '?step=upgrade_prepare');
}

// 一些准备工作
function upgrade_prepare() {

	global $conf;
	
	$db = get_db();
	$db->index_create('thread', array('tid'=>1));
	$db->query("ALTER TABLE {$db->tablepre}thread_type ADD column oldtypeid int(11) NOT NULL default '0';");
	$db->index_create('thread_type', array('oldtypeid'=>1));
	
	message('准备完毕，接下来升级 forum...', '?step=upgrade_forum');
}

function upgrade_forum() {
	global $start, $conf;
	$dx2_attach_path = DX2_ATTACH_PATH;
	
	include DX2_CONF_FILE;
	
	$dx2 = get_dx2();
	$db = get_db();
	$uc = get_uc();
	$count = $dx2->index_count('forum_forum');
	$mthread_type = new thread_type($conf);
	$mthread_type_cate = new thread_type_cate($conf);
	$mforum_access = new forum_access($conf);
	$groupids = array(0, 1, 2, 3, 4, 5, 6, 7, 11, 12, 13, 14, 15);
	if($start < $count) {
		$limit = DEBUG ? 10 : 500;	// 每次升级 100
		$arrlist = $dx2->index_fetch_id('forum_forum', 'fid', array(), array(), $start, $limit);
		foreach($arrlist as $key) {
			list($table, $col, $fid) = explode('-', $key);
			$old = $dx2->get("forum_forum-fid-$fid");
			$old2 = $dx2->get("forum_forumfield-fid-$fid");
			
			// fix dx2 empty forumname
			if(empty($old['name'])) continue;
			
			// 群组，隐藏掉
			if($old['status'] == 3) {
				$old['status'] = 0;
				$old['name'] .= '(群组)';
				continue;	// 群组数据抛弃，thread, post 都应该抛弃，发帖数，回复数都得重新统计！
			}
			
			// 忽略上级版块
			if($old['fup'] == 0) {
				continue;
			}
			
			// 判断是否存在
			$forum = $db->get("forum-fid-$fid");
			// 多次升级，更新数据
			if(!empty($forum)) {
				$forum['name'] = strip_tags($old['name']);
				// todo: 如果为隐藏版块，则对 forum_access 增加记录
				if($old['status'] != 1) {
					foreach($groupids as $groupid) {
						$groupid = intval($groupid);
						$access = array();
						$access['allowread'] = ($groupid == 1 ? 1 : 0);
						$access['allowpost'] = 0;
						$access['allowthread'] = 0;
						$access['allowdown'] = 0;
						$access['allowattach'] = 0;
						$access['allowdown'] = 0;
						$access['fid'] = $fid;
						$access['groupid'] = $groupid;
						$this->forum_access->create($access);
					}				
				}
				$forum['threads'] = intval($old['threads']);
				$forum['posts'] = intval($old['posts']);
				$db->update($forum);
			} else {
				// a:6:{s:8:"required";b:1;s:8:"listable";b:0;s:6:"prefix";s:1:"0";s:5:"types";a:3:{i:1;s:7:"fenlei1";i:2;s:7:"fenlei2";i:3;s:7:"fenlei3";}s:5:"icons";a:3:{i:1;s:0:"";i:2;s:0:"";i:3;s:0:"";}s:10:"moderators";a:3:{i:1;N;i:2;N;i:3;N;}}
				// 主题分类一块升
				if($old2['threadtypes']) {
					$threadtypes = dx2_unserialize($old2['threadtypes'], $_config['db']['1']['dbcharset']);
					if(!empty($threadtypes)) {
						$threadtype = $threadtypes['types'];
						$newtypeid = 0;
						foreach($threadtype as $typeid=>$typename) {
							$newtypeid++;
							$arr = array(
								'fid'=>$fid,
								'typeid'=>$newtypeid,
								'oldtypeid'=>$typeid,
								'threads'=>0,
								'typename'=>str_replace(array("\r", "\n"), array('', ''), strip_tags($typename)),
								'rank'=>0,
							);
							$db->set("thread_type-typeid-$typeid", $arr);
						}
					}
				}
				
				//5	subjectxxx	1343525778	star
				if($old['lastpost']) {
					$last = explode("\t", $old['lastpost']);
					$last[0] = intval($last[0]);
					$last[2] = intval($last[2]);
					$last[3] = str_replace('-', '', $last[3]);
					$lastuser = $uc->get("members-username-$last[3]");
					$lastuid = $lastuser['uid'];
				} else {
					$last = array(0, '', 0, '');
					$lastuid = 0;
				}
				
				$arr = array (
					'fid'=> $old['fid'],
					'name'=> strip_tags($old['name']),
					'rank'=> $old['displayorder'],
					'threads'=> $old['threads'],
					'posts'=> $old['posts'],
					'todayposts'=> $old['todayposts'],
					'lasttid'=> $last[0],
					'brief'=> strip_tags($old2['description']),
					'accesson'=> 0,
					'modids'=> '',
					'modnames'=> '',
					'toptids'=> '',
					'orderby'=> 0,
					'seo_title'=> $old2['seotitle'],
					'seo_keywords'=> $old2['keywords'],
				);
				$db->set("forum-fid-$fid", $arr);
			}
		}
		
		$start += $limit;
		message("正在升级 forum, 一共: $count, 当前: $start...", "?step=upgrade_forum&start=$start", 0);
	} else {	
		message('升级 forum 完成，接下来升级 thread ...', '?step=upgrade_thread&start=0');
	}
}

function upgrade_thread() {
	global $start, $conf;
	$dx2 = get_dx2();
	$db = get_db();
	$uc = get_uc();
	$count = $dx2->index_count('forum_thread');
	
	if($start < $count) {
		$limit = DEBUG ? 10 : 1000;	// 每次升级 100
		$arrlist = $dx2->index_fetch_id('forum_thread', 'tid', array(), array(), $start, $limit);
		foreach($arrlist as $key) {
			list($table, $_, $tid) = explode('-', $key);
			$old = $dx2->get("forum_thread-tid-$tid");
			$fid = $old['fid'];
			//if($old['status'] == 0) continue;
			if($old['displayorder'] == -1) continue;
			
			if($old['lastposter']) {
				$old['lastposter'] = str_replace('-', '', $old['lastposter']);
				$lastuser = $uc->get("members-username-$old[lastposter]");
				$lastuid = $lastuser['uid'];
			} else {
				$lastuid = 0;
			}
			
			$arr = array (
				'fid'=> $old['fid'],
				'tid'=> $old['tid'],
				'username'=> rename_system_user($old['author']),
				'uid'=> $old['authorid'],
				'subject'=> $old['subject'],
				'dateline'=> $old['dateline'],
				'lastpost'=> $old['lastpost'],
				'lastuid'=> $lastuid,
				'lastusername'=> rename_system_user($old['lastposter']),
				'views'=> $old['views'],
				'posts'=> ($old['replies'] + 1),
				'top'=> $old['displayorder'],
				'typeid1'=> $old['typeid'],
				'typeid2'=> 0,
				'typeid3'=> 0,
				'typeid4'=> 0,
				'attachnum'=> $old['attachment'],
				'imagenum'=> 0,
				'modnum'=> 0,
				'closed'=> $old['closed'],
				'firstpid'=> $firstpid,
			);
			$db->set("thread-fid-$fid-tid-$tid", $arr);
			$db->set("thread_view-tid-$tid", array('tid'=>$tid, 'views'=>$old['views']));
			
			// 置顶主题
			if($old['displayorder'] > 0) {
				if($old['displayorder'] == 3) {
					$mruntime = new runtime($conf);
					$runtime = $mruntime->xget();
					$runtime['toptids'] .= trim($runtime['toptids'])." $fid-$tid";
					$mruntime->xset('toptids', $runtime['toptids']);
				} elseif($old['displayorder'] == 2 || $old['displayorder'] == 1) {
					$mforum = new forum($conf);
					$forum = $mforum->read($fid);
					if(substr_count($forum['toptids'], ' ', 0) < 8) {
						$forum['toptids'] = trim($forum['toptids'])." $fid-$tid";
						$mforum->update($forum);
					} else {
						// 创建一个主题分类叫：热门。
						
						/*
						$mthreadtype = new thread_type($conf);
						$typelist = $mthreadtype->index_fetch(array('fid'=>$fid), array(), 0, 1000);
						!empty($typelist) && $typelist = misc::arrlist_key_values($typelist, 'typename', 'typeid');
						if(!isset($typelist['热门'])) {
							$type = array(
									'fid'=>$fid,
									'typename'=>'热门',
									'rank'=>0,
									'enable'=>1,
								);
							$typeid = $mthreadtype->create($type);
						} else {
							$typeid = $typelist['热门'];
						}
						
						// 归类到主题分类，<span class="blue">热门</span>
						$arr['typeid1'] = $typeid;
						//$arr['typename'] = '热门';
						$db->set("thread-fid-$fid-tid-$tid", $arr);
						*/
					}
				}
			}
			
			// mypost
			$arr = array (
				'uid'=>$old['authorid'],
				'fid'=>$old['fid'],
				'tid'=>$old['tid'],
				'pid'=>$firstpid,
			);
			try {
				$db->set("mypost-uid-$thread[uid]-fid-$fid-pid-$firstpid", $arr);
			} catch(Exception $e) {
				continue;
			}
		}
		
		$start += $limit;
		message("正在升级 thread, 一共: $count, 当前: $start...", "?step=upgrade_thread&start=$start", 0);
	} else {	
		message('升级 thread 完成，接下来升级 upgrade_thread_type...', '?step=upgrade_thread_type&start=0');
	}
}

// 典型的跳转框架
function upgrade_thread_type() {
	global $start;
	global $conf;
	$db = get_db();
	
	$count = core::gpc('count');
	if(empty($count)) {
		$count = $db->index_count('thread');
	}
	$thread_type_data = new thread_type_data($conf);
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$threadlist = $db->index_fetch('thread', 'tid', array(), array(), $start, $limit);
		foreach($threadlist as $thread) {
			if($thread['typeid'] > 0) {
				$type = $db->get('thread_type-oldtypeid-'.$thread['typeid1']);
				$thread['typeid1'] = $type['typeid'];
				$thread['typeid2'] = 0;
				$thread['typeid3'] = 0;
				$thread['typeid4'] = 0;
				$thread_type_data->xcreate($thread['fid'], $thread['tid'], $type['newtypeid'], 0, 0);
			}
		}
		$start += $limit;
		message("正在升级 upgrade_thread_type, 一共: $count, 当前: $start...", "?step=upgrade_thread_type&start=$start&count=$count", 0);
	} else {
		message('升级 thread_type 完成，接下来升级 attach...', '?step=upgrade_attach');
	}
}

function upgrade_attach() {
	global $start, $conf;
	$dx2_attach_path = DX2_ATTACH_PATH;
	$dx2 = get_dx2();
	$db = get_db();
	$count = $dx2->index_count('forum_attachment');
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;
		$arrlist = $dx2->index_fetch_id('forum_attachment', 'aid', array(), array(), $start, $limit);
		foreach($arrlist as $key) {
			list($table, $keyname, $aid) = explode('-', $key);
			$attach = $dx2->get("forum_attachment-aid-$aid");
			$tableid = $attach['tableid'];
			// fix: dx2 的附件存储到错误的表(127), bug
			try {
				$old = $dx2->get("forum_attachment_$tableid-aid-$aid");
			} catch(Exception $e) {
				continue;
			}
			
			$oldattach = '';
			is_file($dx2_attach_path.'forum/'.$old['attachment']) && $oldattach = $dx2_attach_path.'forum/'.$old['attachment'];
			is_file(DX2_PATH.$old['attachment']) && $oldattach = DX2_PATH.$old['attachment'];
			if(empty($oldattach)) continue;
			
			$filetype = get_filetype($old['filename']);
			if($filetype == 'image') {
				list($width, $height, $type, $attr) = getimagesize($oldattach);
			} else {
				$height = 0;
			}
			
			// copy
			$ext = strrchr($old['filename'], '.');
			$pathadd = image::set_dir($aid, $conf['upload_path'].'attach/');
			$newfilename = $pathadd.'/'.$aid.$ext;
			$newfile = $conf['upload_path'].'attach/'.$newfilename;
			!is_file($newfile) && copy($oldattach, $newfile);
			$forum = $dx2->get("forum_thread-tid-$old[tid]");
			
			$arr = array (
				'fid'=> intval($forum['fid']),
				'aid'=> intval($old['aid']),
				'pid'=> intval($old['pid']),
				'tid'=> intval($old['tid']),
				'uid'=> intval($old['uid']),
				'filesize'=> intval($old['filesize']),
				'width'=> intval($old['width']),
				'height'=> intval($height),
				'filename'=> $newfilename,
				'orgfilename'=> $old['filename'],
				'filetype'=> $filetype,
				'dateline'=> intval($old['dateline']),
				'comment'=> $old['description'],
				'downloads'=> 0,
				'isimage'=> 0,
				'golds'=> 0,
			);
			$db->set("attach-aid-$aid", $arr);
		}
		
		$start += $limit;
		message("正在升级 attach, 一共: $count, 当前: $start...", "?step=upgrade_attach&start=$start", 0);
	} else {	
		message('升级 attach 完成，接下来升级 post ...', '?step=upgrade_post&start=0');
	}
}

function upgrade_post() {
	global $start, $conf;
	
	$dx2 = get_dx2();
	$db = get_db();
	$count = $dx2->index_count('forum_post');
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;	// 每次升级 100
		$arrlist = $dx2->index_fetch_id('forum_post', 'pid', array(), array(), $start, $limit);
		foreach($arrlist as $key) {
			list($table, $_, $pid) = explode('-', $key);
			$old = $dx2->get("forum_post-pid-$pid");
			$fid = $old['fid'];
			
			// 帖子附件
			if($old['attachment']) {
				$attachlist = $db->index_fetch('attach', 'aid', array('fid'=>$fid, 'pid'=>$pid), array('aid'=>1), array(), 0, 1000);
				if($attachlist) {
					foreach($attachlist as $attach) {
						$attachinsert = '[attach]'.$attach['aid'].'[/attach]';
						if(strpos($old['message'], $attachinsert) !== FALSE) {
							$old['message'] = str_replace($attachinsert, get_attach_html($attach), $old['message']);
						} else {
							$old['message'] .= get_attach_html($attach);
						}
					}
				}
				// 如果没有 aid 不在 message 中，则直接粘贴到内容末尾
			}
			$old['message'] = bbcode2html($old['message']);
			
			//$s = preg_replace('#\[attach\]([^[]*?)\[/attach\]#i', '', $s);
			
			$arr = array (
				'fid'=> intval($old['fid']),
				'pid'=> intval($old['pid']),
				'tid'=> intval($old['tid']),
				'uid'=> intval($old['authorid']),
				'dateline'=> intval($old['dateline']),
				'userip'=> 0,
				'attachnum'=> intval($old['attachment']),
				'imagenum'=> 0,
				'page'=> 1,
				'username'=> rename_system_user($old['author']),
				'subject'=> $old['subject'],
				'message'=> $old['message'],
			);
			
			$db->set("post-fid-$fid-pid-$pid", $arr);
		}
		
		$start += $limit;
		message("正在升级 post, 一共: $count, 当前: $start...", "?step=upgrade_post&start=$start", 0);
	} else {	
		message('升级 post，接下来升级 user...', '?step=upgrade_user&start=0');
	}
}

// 升级头像，一次1000，跳转升级。一百万用户需要跳转1000次。一次大概5秒。5000秒。大概2小时。
function upgrade_user() {
	global $start;
	$uc_avatar_path = DX2_AVATAR_PATH;
	$conf = include BBS_PATH.'conf/conf.php';
	
	if(!is_dir($uc_avatar_path)) {
		message('头像目录不存在，请将头像目录移动到：'.$uc_avatar_path.' 后，然后刷新本页。');
	}
	
	$uc = get_uc();
	$db = get_db();
	$dx2 = get_dx2();
	
	$start_time = microtime(1);
	
	$count = isset($_GET['count']) ? intval($_GET['count']) : $uc->index_count('members');
	
	if($start < $count) {
		$limit = DEBUG ? 20 : 2000;	// 每次升级 100
		$arrlist = $uc->index_fetch_id('members', 'uid', array(), array(), $start, $limit);
		
		foreach($arrlist as $key) {
			list($table, $col, $uid) = explode('-', $key);
			
			$old1 = $uc->get("members-uid-$uid");
			$old2 = $dx2->get("common_member-uid-$uid");
			$old3 = $dx2->get("common_member_count-uid-$uid");
			$old4 = $dx2->get("common_member_status-uid-$uid");
			$old5 = $dx2->get("common_member_profile-uid-$uid");
			
			if(empty($old2)) {
				$old2 = array('avatarstatus'=>0, 'groupid'=>0, 'adminid'=>0);
			}
			$oldavatarfile = $uc_avatar_path.get_avatar($uid, 'big');
			if($old2['avatarstatus'] && is_file($oldavatarfile) && filesize($oldavatarfile) < 200000) {
				
				$hugepath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_huge.gif';
				$bigpath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_big.gif';
				$middlepath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_middle.gif';
				$smallpath = $conf['upload_path'].'avatar/'.image::set_dir($uid, $conf['upload_path'].'avatar/').'/'.$uid.'_small.gif';
				!is_file($hugepath) && image::thumb($oldavatarfile, $hugepath, $conf['avatar_width_huge'], $conf['avatar_width_huge']);
				!is_file($bigpath) && image::thumb($oldavatarfile, $bigpath, $conf['avatar_width_big'], $conf['avatar_width_big']);
				!is_file($middlepath) && image::thumb($oldavatarfile, $middlepath, $conf['avatar_width_middle'], $conf['avatar_width_middle']);
				!is_file($smallpath) && image::thumb($oldavatarfile, $smallpath, $conf['avatar_width_small'], $conf['avatar_width_small']);
			} else {
				$old2['avatarstatus'] = 0;
			}
			
			if($old3['posts'] > 0) {
				$myposts = $db->fetch_first("SELECT COUNT(*) AS num FROM {$db->tablepre}mypost WHERE uid='$uid'");
				$myposts = !empty($myposts) ? intval($myposts['num']) : 0;
			} else {
				$myposts = 0;
			}
			
			
			// todo:only bt
			$credits = $old3['extcredits2'] * 1 + $old3['extcredits3'] * 4 + $old3['extcredits4'] * 40 + $old3['extcredits5'] * 2;
			//$credits = $old3['threads'] * 2 + $old3['posts'];
			
			// email 为空
			if(empty($old1['email'])) {
				$old1['email'] = $old1['uid'].'@'.$_SERVER['HTTP_HOST'];
			}
			// 判断 email 是否已经存在，可能会重复，这里比较恶心... 一个email居然可以对应多个账号，太混乱了。
			$useremail = $db->index_fetch('user', 'uid', array('email'=>$old1['email']), array(), 0, 1);
			if(!$empty($useremail)) {
				 $old1['email'] = $old1['uid'].'@'.$_SERVER['HTTP_HOST'];
			}
			
			$arr = array (
				'uid'=> intval($uid),
				'regip'=> ip2long($old4['regip']),
				'regdate'=> intval($old1['regdate']),
				'username'=> rename_system_user($old1['username']),
				'password'=> $old1['password'],
				'salt'=> $old1['salt'],
				'email'=> $old1['email'],
				'groupid'=> get_groupid($credits, $old2['groupid'], $old2['adminid']),
				'threads'=> intval($old3['threads']),
				'posts'=> intval($old3['posts']),
				'myposts'=> $myposts,	// todo: 后面统计
				'avatar'=> intval($old2['avatarstatus']),
				'credits'=> intval($credits),
				'golds'=> $old3['extcredits1'],
				'follows'=> 0,
				'followeds'=> 0,
				'newpms'=> 0,
				'newfeeds'=> 0,
				'homepage'=> '',
				'accesson'=> 0,
				'onlinetime'=> 0,
				'lastactive'=> $old4['lastactivity'],
			);
			$db->set("user-uid-$uid", $arr);
			
			
			$arr = array(
				'gender'=>$old5['gender'],
				'birthyear'=>$old5['birthyear'],
				'birthmonth'=>$old5['birthmonth'],
				'birthday'=>$old5['birthday'],
				'province'=>$old5['resideprovince'],
				'city'=>$old5['residecity'],
				'county'=>$old5['residedist'],
			);
			$db->set("user_ext-uid-$uid", $arr);
		
		}
		
		$start += $limit;
		
		$processtime = intval(microtime(1) - $start_time);
		$remaintime = intval($processtime * (($count - $start) / $limit));
		$remain_hour = intval($remaintime / 3500);
		$remain_min = intval(($remaintime % 3600) / 60);
		$remain_sec = intval(($remaintime % 3600) % 60);
		message("正在升级 user, 一共: $count, 当前: $start... （本次耗时：$processtime 秒，大约还需要 $remain_hour 小时, $remain_min 分钟， $remain_sec 秒 ）", "?step=upgrade_user&start=$start&count=$count", 0);
	} else {
		// 生成系统用户，系统用户名：系统，如果发现重名，则改名。
		//INSERT INTO bbs_user SET uid='2', regip='12345554', regdate=UNIX_TIMESTAMP(), username='系统', password='d14be7f4d15d16de92b7e34e18d0d0f7', salt='99adde', email='system@admin.com', groupid='11', golds='0';
		$maxuid = $db->index_maxid('user-uid') + 1;
		$db->maxid('user-uid', $maxuid);
		$admin = $db->get("user-uid-1");
		$arr = array (
			'uid'=> $maxuid,
			'regip'=> 0,
			'regdate'=> $_SERVER['time'],
			'username'=> '系统',
			'password'=> $admin['password'],
			'salt'=> $admin['salt'],
			'email'=> 'system@admin.com',
			'groupid'=> 11,
			'threads'=> 0,
			'posts'=> 0,
			'myposts'=> 0,	// todo: 后面统计
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
		
		// 写入配置
		$kv = new kv($conf);
		$kv->xset('system_uid', $maxuid);
		$kv->xset('system_username', '系统');
		$kv->xsave();
		
		message('升级 user 完成，接下来升级 pm ...', '?step=upgrade_pm&start=0');
	}
}

function upgrade_pm() {
	message('升级 pm 完成，接下来升级 ...', '?step=upgrade_friendlink&start=0');
}

function upgrade_friendlink() {
	global $conf;
	$dx2 = get_dx2();
	$db = get_db();
	$arrlist = $dx2->index_fetch('common_friendlink', 'id', array(), array(), 0, 1000);
	foreach($arrlist as $old) {
		$arr = array (
			'linkid'=> intval($old['id']),
			'type'=> $old['logo'] ? 1 : 0,
			'rank'=> intval($old['displayorder']),
			'sitename'=> $old['name'],
			'url'=> $old['url'],
			'logo'=> $old['logo'],
		);
		$db->set("friendlink-linkid-$old[id]", $arr);
	}
		
	message('升级 friendlink 完成，接下来升级 mod...', '?step=upgrade_mod&start=0');
}

function upgrade_mod() {
	message('升级 mod 完成，接下来升级 stat...', '?step=upgrade_stat&start=0');
}

function upgrade_stat() {
	message('升级 stat 完成，接下来升级 postpage...', '?step=upgrade_postpage&start=0');
}

function upgrade_postpage() {
	global $start, $start2, $conf;
	
	include DX2_CONF_FILE;
	$dx2_tablepre = $_config['db'][1]['tablepre'];

	$dx2 = get_dx2();
	$db = get_db();
	$count = $dx2->index_count('forum_thread');
	if($start < $count) {
		$limit = DEBUG ? 10 : 2000;	// 每次升级 100
		$limit2 = DEBUG ? 20 : 2000;
		$tidkeys = $dx2->index_fetch_id('forum_thread', array('tid'), array(), array(), $start, $limit);
		foreach($tidkeys as $key) {
			list($table, $_, $tid) = explode('-', $key);
			$thread = $dx2->get("forum_thread-tid-$tid");
			$fid = $thread['fid'];
			
			if($thread['replies'] <= 19) {
				$start += 1;
				continue;
			}
			
			if(empty($start2) || !isset($_GET['count2'])) {
				$count2 = $dx2->fetch_first("SELECT COUNT(*) AS num FROM {$dx2_tablepre}forum_post WHERE tid='$tid'");
				$count2 = intval($count2['num']);
			} else {
				$count2 = intval($_GET['count2']);
			}
			
			while($start2 < $count2  && $limit2 > 0) {
				$pidkeys = $dx2->index_fetch_id('forum_post', array('pid'), array('tid'=>$tid), array('pid'=>1), $start2, $limit2);
				$i = 0;
				foreach($pidkeys as $key2) {
					$i++;
					list($table, $_, $pid) = explode('-', $key2);
					$post = $db->get("post-fid-$fid-pid-$pid");
					$page = max(1, ceil(($start2 + $i) / 20));
					$post['page'] = $page;
					if($conf['db']['type'] == 'mysql') {
						// 提高写入速度
						$db->query("UPDATE {$db->tablepre}post SET page='$page' WHERE fid='$fid' AND pid='$pid'");
					} else {
						$db->set("post-fid-$fid-pid-$pid", $post);
					}
				}
				
				$n = count($pidkeys);
				$limit2 -= $n;
				$start2 += $n;
				//echo "start: $start,  limit: $limit, count: $count, start2: $start2, limit2: $limit2,  count2: $count2, n: $n, tid: $tid";
				if(empty($n)) break;
			}
			
			if($limit2 <= 0) {
				break;
			} else {
				$start2 = 0;
				$start += 1;
			}
		}
		message("正在升级 post.page, 进度 thread: $start / $count, post: $start2 / $count2...", "?step=upgrade_postpage&start=$start&start2=$start2&count2=$count2", 0);
	} else {	
		message('升级 upgrade_postpage 完成，接下来升级 upgrade_forum2 ...', '?step=upgrade_forum2&start=0');
	}
}

// 第二次升级 forum
function upgrade_forum2() {
	// 放到最后一步。
	$dx2 = get_dx2();
	$db = get_db();	

	$forumlist = $db->index_fetch('forum', 'fid', array(), array(), 0, 2000);
	foreach($forumlist as $forum) {
		
		$modids = $modnames = '';
		$modlist = $dx2->index_fetch('forum_moderator', array('uid', 'fid'), array('fid'=>$fid, 'inherited'=>0), array(), 0, 12);
		$modlist = array_slice($modlist, 0, 6);
		foreach($modlist as $mod) {
			$user = $dx2->get("common_member-uid-$mod[uid]");
			$modids .= (empty($modids) ? '' : ' ').$mod['uid'];
			$modnames .= (empty($modnames) ? '' : ' ').$user['username'];
		}
		
		$forum['modids'] = $modids;
		$forum['modnames'] = $modnames;
		$db->update("forum-fid-$id", $forum);
	}
	message('升级 upgrade_postpage 完成，接下来升级 laststep ...', '?step=laststep&start=0');
}

function laststep() {
	global $conf;
	clear_tmp('');
	$db = get_db();
	
	// 重新统计 thread_types.threads 最多3000个主题分类，够了吧。
	$thread_type_list = $db->index_fetch('thread_type', 'typeid', array(), array(), 0, 3000);
	foreach($thread_type_list as $thread_type) {
		// 统计
		$typeid = $thread_type['typeid'];
		$arr = $db->fetch_first("SELECT COUNT(*) AS num FROM {$db->tablepre}thread WHERE typeid='$typeid'");
		$n = $arr['num'];
		$thread_type['threads'] = $n;
		$db->set("thread_type-typeid-$typeid", $thread_type);
	}
	
	// 清空置顶
	$db = get_db();
	
	// 同步
	// copy  from install_mongodb		
	$maxs = array(
		'group'=>'groupid',
		'user'=>'uid',
		'user_access'=>'uid',
		'forum'=>'fid',
		'forum_access'=>'fid',
		'thread_type'=>'typeid',
		'thread'=>'tid',
		'post'=>'pid',
		'attach'=>'aid',
		'attach_download'=>'aid',
		'friendlink'=>'linkid',
		'pm'=>'pmid',
	);
	
	foreach($maxs as $table=>$maxcol) {
		$m = $db->index_maxid($table.'-'.$maxcol);
		$db->maxid("$table-$maxcol", $m);
		
		$n = $db->index_count($table);
		$db->count($table, $n);
	}
	
	$db->truncate('kv');
	$db->truncate('runtime');
	
	// todo: 清理 thread
	//$db->index_drop('oldtypeid');
	//$db->query("ALTER TABLE {$db->tablepre}thread_type DROP COLUMN oldtypeid;");
	
	// 修改管理员用户组
	message('升级完毕，请<b>删除 upgrade 目录</b>，防止重复升级！！！<a href="../">【进入论坛】</a>');
}

function int_to_string($arr) {
	$s = '';
	foreach($arr as $v) {
		$a = sprintf('%08x', $v);
		$b = '';
		// int 在内存中为逆序存放
		$b .= chr(base_convert(substr($a, 6, 2), 16, 10));
		$b .= chr(base_convert(substr($a, 4, 2), 16, 10));
		$b .= chr(base_convert(substr($a, 2, 2), 16, 10));
		$b .= chr(base_convert(substr($a, 0, 2), 16, 10));
		//echo $a;
		$s .= $b;
	}
	return $s;
}


// dx2 db instance
function get_dx2() {
	include DX2_CONF_FILE;
	$dx2 = new db_mysql(array(
		'master' => array (
			'host' => $_config['db'][1]['dbhost'],
			'user' => $_config['db'][1]['dbuser'],
			'password' => $_config['db'][1]['dbpw'],
			'name' => $_config['db'][1]['dbname'],
			'charset' => 'utf8',	// 要求取出 utf-8 数据 mysql 4.1 以后支持转码
			//'charset' => $_config['db'][1]['dbcharset'],
			'tablepre' => $_config['db'][1]['tablepre'],
			'engine'=>'MyISAM',
		),
		'slaves' => array ()
	));
	// 要求返回的数据为 utf8
	return $dx2;
}

// ucenter db instance
function get_uc() {
	include UCENTER_CONF_FILE;;
	$tablepre = explode('.', UC_DBTABLEPRE);
	$uc = new db_mysql(array(
		'master' => array (
			'host' => UC_DBHOST,
			'user' => UC_DBUSER,
			'password' => UC_DBPW,
			'name' => UC_DBNAME,
			'charset' => 'utf8',	// 要求取出 utf-8 数据 mysql 4.1 以后支持转码
			//'charset' => UC_DBCHARSET,
			'tablepre' => $tablepre[1],
			'engine'=>'MyISAM',
		),
		'slaves' => array ()
	));
	return $uc;
}

// xn2 db instance
function get_db() {
	$conf = include BBS_PATH.'conf/conf.php';
	$db = new db_mysql($conf['db'][$conf['db']['type']]);
	return $db;
}

// 获取升级的进度，保存 step 和 start 到 tmp
function loading_upgrade_process(&$step, &$start, &$start2) {
	$conf = include BBS_PATH.'conf/conf.php';
	$file = $conf['tmp_path'].'upgrade_process.txt';
	if(is_file($file)) {
		$s = file_get_contents($file);
		if($s) {
			$arr = explode(' ', $s);
			$step = $arr[0];
			$start = $arr[1];
			$start2 = $arr[2];
			return;
		}
	}
	$step = '';
	$start = 0;
	$start2 = 0;
	return;
}

function save_upgrade_process() {
	global $start, $start2, $step;
	$conf = include BBS_PATH.'conf/conf.php';
	$file = $conf['tmp_path'].'upgrade_process.txt';
	file_put_contents($file, "$step $start $start2");
}

function message($s, $url = '', $timeout = 2) {
	DEBUG && $timeout = 1000;
	global $conf;
	
	$s = $url ? "<h2>$s</h2><p><a href=\"$url\">页面将在<b>$timeout</b>秒后自动跳转，点击这里手工跳转。</a></p>
		<script>
			setTimeout(function() {
				window.location=\"$url\";
				setInterval(function() {
					window.location=\"$url\";
				}, 30000);
			}, ".($timeout * 1000).");
		</script>
	" : "<h2>$s</h2>";
	echo '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Discuz!X 2.0 转 Xiuno BBS 2.0.0 RC3 程序 </title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="../view/common.css" />
	</head>
	<body>
	<div id="header" style="overflow: hidden;">
		<h3 style="color: #FFFFFF; line-height: 26px;margin-left: 16px;">Discuz! 2.0 转 Xiuno BBS 2.0.0 RC3 程序</h3>
		<p style="color: #BBBBBB; margin-left: 16px;">本程序会记录上次升级的进度，如果需要重头转换，请删除进度记录文件'.$conf['tmp_path'].'upgrade_process.txt'.'</p>
	</div>
	<div id="body" style="padding: 16px;">
		'.$s.'
	</div>
	<div id="footer"> Powered by Xiuno (c) 2010 </div>
	<div style="color: #888888;">'.(DEBUG ? nl2br(print_r($_SERVER['sqls'], 1)) : '').'</div>
	</body>
	</html>';
	
	save_upgrade_process();
	exit;
}

function clear_tmp($pre) {
	global $conf;
	if(IN_SAE) {
		$kv = new SaeKV();
		$ret = $kv->pkrget($pre, 100);
		foreach($ret as $key=>$val) {
			$kv->delete($key);
		}
	} else {
		$dh = opendir($conf['tmp_path']);
		while(($file = readdir($dh)) !== false ) {
			if($file != "." && $file != ".." ) {
				if(substr($file, 0, strlen($pre)) == $pre) {
					unlink($conf['tmp_path']."$file");
				}
			}
		}
		closedir($dh);
	}
}

/*
function file_line_replace($configfile, $startline, $endline, $replacearr) {
	// 从16行-33行，正则替换
	$arr = file($configfile);
	$arr1 = array_slice($arr, 0, $startline - 1); // 此处: startline - 1 为长度
	$arr2 = array_slice($arr, $startline - 1, $endline - $startline + 1); // 此处: startline - 1 为偏移量
	$arr3 = array_slice($arr, $endline);
	
	$s = implode("", $arr2);
	foreach($replacearr as $k=>$v) { 
		$s = preg_replace('#\''.preg_quote($k).'\'\s*=\>\s*\'?.*?\'?,#ism', "'$k' => '$v',", $s);
	}
	$s = implode("", $arr1).$s.implode("", $arr3);
	file_put_contents($configfile, $s);
}
*/

// uc_server 头像存储规则:
function get_avatar($uid, $size = 'middle', $type = '') {
	$size = in_array($size, array('huge', 'big', 'middle', 'small')) ? $size : 'middle';
	$uid = abs(intval($uid));
	$uid = sprintf("%09d", $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}

function get_filetype($filename) {
	 	
	$filetypes = array (
		'av' => array('av', 'wmv', 'wav', 'wma', 'avi'),
		'real' => array('rm', 'rmvb'),
		'mp3' => array('mp3','mp4'),
		'binary' => array('dat'),
		'flash' => array('swf'),
		'html' => array('html', 'htm'),
		'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
		'office' => array('doc', 'xls', 'ppt'),
		'pdf' => array('pdf'),
		'rar' => array('rar'),
		'text' => array('txt'),
		'bt' => array('bt'),
		'zip' => array('tar','zip', 'gz'),
		'book' => array('chm'),
		'torrent' => array('torrent')
	);
	$ext = strtolower(substr(strrchr($filename, '.'), 1));
	foreach($filetypes as $type=>$arr) {
		if(in_array($ext, $arr)) {
			return $type;
		}
	}
	return 'unknow';
}


function get_attach_html($attach) {
	global $conf;
	if($attach['filetype'] == 'image') {
		return "<li><img src=\"$conf[static_url]upload/attach/$attach[filename]\" width=\"$attach[width]\" height=\"$attach[height]\"/></li>";
	} else {
		$fileicon = "<img src=\"$conf[static_url]view/image/filetype/$attach[filetype].gif\" width=\"16\" height=\"16\" />";
		return "<li><a href=\"$conf[static_url]upload/attach/$attach[filename]\" target=\"_blank\">$fileicon $attach[orgfilename]</a></li>";
	}
}


/*
[hide]...[/hide]
[attach]..[/attach]
分页 checked
catename color stripvtags
{:smile:}{:smile:}
*/

function bbcode2html($s, $parseurl=1) {
	$s = str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $s);
	$s = nl2br($s);
	$s = preg_replace('#(<br\s*/?>\s*){3,}#', '<br /><br />', $s);
	
	$s = str_replace(array(
		'[b]', '[/b]','[i]', '[i=s]', '[/i]', '[u]', '[/u]', '[/color]', '[/size]', '[/font]', 
		'[p]', '[/p]', '[/align]', '[/list]', '[/td]', '[/tr]', '[/table]', '[td]', '[tr]', '[table]', 
		'[hr]', '[quote]', '[/quote]', '[hide]', '[/hide]'), array(
		'<b>', '</b>', '<i>', '<i>', '</i>', '<u>', '</u>', '</font>', '</font>', '</font>', 
		'<p>', '</p>', '</div>', '</ul>', '</td>', '</tr>', '</table>', '<td>', '<tr>', '<table>', 
		'<hr />', '<div class="quote">', '</div>', '', ''), $s);
	$s = preg_replace('#\[em:([0-9]+):\]#i', '', $s);
	$s = preg_replace('#\[quote\]([^[]*?)\[/quote\]#i', '<div class="bg2 border shadow">\\1</div>', $s);
	$s = preg_replace('#\[color=([^]]+)\]#i', '<font color="\\1">', $s);
	$s = preg_replace('#\[size=(\w+)\]#i', '<font size="\\1">', $s);
	$s = preg_replace('#\[font=([^]]+)\]#i', '<font="\\1">', $s);
	$s = preg_replace('#\[align=([^]]+)\]#i', '<div align="\\1">', $s);
	$s = preg_replace('#\[table=([^]]+)\]#i', '<table width="\\1">', $s);
	$s = preg_replace('#\[td=([^]]+)\]#i', '<td width="\\1">', $s);
	$s = preg_replace('#\[tr=([^[]+)\]#i', '<tr>', $s);
	$s = preg_replace('#\[p=([^]]+)\]#i', '<p>', $s);
	$s = preg_replace('#\[list=([^]]+)\]#i', '<ul>', $s);
	$s = preg_replace('#\{:[^}]+:\}#i', '', $s);
	$s = preg_replace('#\[\*\](.*?)\r\n#i', '<li>\\1</li>', $s);
	$s = preg_replace('#\[url\](.*?)\[\/url\]#i', "<a href=\"\\1\" target=\"_blank\">\\1</a>", $s);
	$s = preg_replace('#\[url=([^]]+?)\](.*?)\[\/url\]#i', "<a href=\"\\1\" target=\"_blank\">\\2</a>", $s);
	$s = preg_replace('#\[backcolor=([^]]+?)\]([^[]*?)\[\/backcolor\]#i', "<div style=\"background: \\1\">\\2</div>", $s);
	$s = preg_replace('#\[indent\]([^[]*?)\[\/indent\]#i', "<ul>\\1</ul>", $s);
	$s = preg_replace('#\[img\]([^[]*?)\[/img\]#i', '<img src="\\1" />', $s);
	$s = preg_replace('#\[img=(\d+),(\d+)\]([^[]*?)\[/img\]#i', '<img src="\\3" width="\\1" height="\\2" />', $s);
	
	$s = preg_replace('#\[attach\]([^[]*?)\[/attach\]#i', '', $s);
	
	$s = preg_replace('#\[media=\w+,(\d+),(\d+)\]([^[]*?)\[/media\]#i', '[media=\\1,\\2]\\3[/media]', $s);
	
	$s = preg_replace('#\[flash]([^[]*?)\[/flash\]#i', '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase=" http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="400" height="300">
			<param name="wmode" value="transparent" />
			<param name="quality" value="high" />
			<param name="menu" value="false" />
			<param name="loop" value="false" />
			<param name="AutoStart " value="true" />
			<param name="src" value="\\1" />
			<embed src="\\1" quality="high" AutoStart="true" loop="false" width="400" height="300" name="firefoxhead" allowFullScreen="yes" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" swLiveConnect="true" />
		</object>', $s);
	
	$s = preg_replace('#\[(media|swf|flash)=(\d+),(\d+)\]([^[]*?)\[/\\1\]#i', '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase=" http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="\\2" height="\\3">
		<param name="wmode" value="transparent" />
		<param name="quality" value="high" />
		<param name="menu" value="false" />
		<param name="loop" value="false" />
		<param name="AutoStart " value="true" />
		<param name="src" value="\\4" />
		<embed src="\\4" quality="high" AutoStart="true" loop="false" width="\\2" height="\\3" name="firefoxhead" allowFullScreen="yes" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" swLiveConnect="true" />
	</object>', $s);
	
	
	
	return $s;
}

function get_groupid($credits, $groupid, $adminid) {
	if($adminid == 1) return 1;
	if($adminid == 2) return 2;
	if($adminid == 3) return 3;
	$grouplist = array(11=>array(0, 50), 12=>array(50, 200), 13=>array(200, 1000), 14=>array(1000, 10000), 15=>array(10000, 10000000));
	foreach($grouplist as $groupid=>$group) {
		if($credits >= $group[0] && $credits < $group[1]) {
			return $groupid;
		}
	}
	return 11;
}

function dx2_unserialize($s, $dbcharset) {
	// fix dx2 bug
	if(strtolower($s) == 'array') {
		return array();
	}
	
	if(strtolower($dbcharset) == 'gbk') {
		$s = iconv('UTF-8', 'GBK', $s);
	}
	$arr = unserialize($s);
	if(strtolower($dbcharset) == 'gbk') {
		foreach($arr as &$v) {
			if(is_string($v)) $v = iconv('GBK', 'UTF-8', $v);
			if(is_array($v)) {
				foreach($v as &$v2) {
					if(is_string($v2)) $v2 = iconv('GBK', 'UTF-8', $v2);
						
				}
			}
		}
	}
	
	return $arr;
}

// 重命名系统用户名为 系统
function rename_system_user($username) {
	return $username == '系统' ? '__系统__' : $username;
}
?>