<?php

$db_table = array(
	'group'=>array (
		array('groupid', 'smallint(6)'),
		array('name', 'char(20)'),
		array('creditsfrom', 'int(10)'),
		array('creditsto', 'int(10)'),
		array('maxcredits', 'int(10)'),
		array('maxgolds', 'int(10)'),
		array('allowread', 'int(10)'),
		array('allowthread', 'int(10)'),
		array('allowpost', 'int(10)'),
		array('allowreply', 'int(10)'),
		array('allowattach', 'int(10)'),
		array('allowdown', 'int(10)'),
		array('allowtop', 'int(10)'),
		array('allowdigest', 'int(10)'),
		array('allowupdate', 'int(10)'),
		array('allowdelete', 'int(10)'),
		array('allowmove', 'int(10)'),
		array('allowbanuser', 'int(10)'),
		array('allowdeleteuser', 'int(10)'),
		array('allowviewip', 'int(10)'),
	),
	'user'=>array (
		array('uid', 'int(11)'),
		array('regip', 'int(11)'),
		array('regdate', 'int(11)'),
		array('username', 'char(16)'),
		array('password', 'char(32)'),
		array('salt', 'char(8)'),
		array('email', 'char(40)'),
		array('groupid', 'tinyint(3)'),
		array('threads', 'mediumint(8)'),
		array('posts', 'int(8)'),
		array('myposts', 'mediumint(8)'),
		array('avatar', 'int(11)'),
		array('credits', 'int(11)'),
		array('golds', 'int(11)'),
		array('digests', 'int(11)'),
		array('follows', 'smallint(3)'),
		array('followeds', 'int(11)'),
		array('newpms', 'int(11)'),
		array('newfeeds', 'int(11)'),
		array('homepage', 'char(40)'),
		array('accesson', 'tinyint(1)'),
		array('onlinetime', 'int(1)'),
		array('lastactive', 'int(1)'),
	),
	'user_access'=>array (
		array('uid', 'int(11)'),
		array('allowread', 'tinyint(1)'),
		array('allowthread', 'tinyint(1)'),
		array('allowpost', 'tinyint(1)'),
		array('allowreply', 'tinyint(1)'),
		array('allowattach', 'tinyint(1)'),
		array('allowdown', 'tinyint(1)'),
		array('expiry', 'int(10)'),
	),
	'forum'=>array (
		array('fid', 'int(11)'),
		array('name', 'char(16)'),
		array('rank', 'tinyint(3)'),
		array('threads', 'mediumint(8)'),
		array('posts', 'int(11)'),
		array('digests', 'int(11)'),
		array('todayposts', 'mediumint(8)'),
		array('lasttid', 'int(11)'),
		array('brief', 'text'),
		array('accesson', 'tinyint(1)'),
		array('modids', 'char(255)'),
		array('modnames', 'char(255)'),
		array('toptids', 'char(240)'),
		array('orderby', 'tinyint(11)'),
		array('seo_title', 'char(64)'),
		array('seo_keywords', 'char(64)'),
	),
	'forum_access'=>array (
		array('fid', 'int(11)'),
		array('groupid', 'int(11)'),
		array('allowread', 'tinyint(1)'),
		array('allowthread', 'tinyint(1)'),
		array('allowpost', 'tinyint(1)'),
		array('allowreply', 'tinyint(1)'),
		array('allowattach', 'tinyint(1)'),
		array('allowdown', 'tinyint(1)'),
	),
	'thread_type_cate'=>array (
		array('fid', 'smallint(6)'),
		array('cateid', 'int(11)'),
		array('catename', 'char(16)'),
		array('rank', 'int(11)'),
		array('enable', 'tinyint(3)'),
	),
	'thread_type'=>array (
		array('fid', 'smallint(6)'),
		array('typeid', 'int(11)'),
		array('typename', 'char(16)'),
		array('rank', 'int(11)'),
		array('enable', 'tinyint(3)'),
	),
	'thread_type_count'=>array (
		array('fid', 'smallint(6)'),
		array('typeidsum', 'int(11)'),
		array('threads', 'int(11)'),
	),
	'thread_type_data'=>array (
		array('fid', 'smallint(6)'),
		array('tid', 'int(11)'),
		array('typeidsum', 'int(11)'),
	),
	'thread'=>array (
		array('fid', 'smallint(6)'),
		array('tid', 'int(11)'),
		array('username', 'char(16)'),
		array('uid', 'int(11)'),
		array('subject', 'char(80)'),
		array('dateline', 'int(10)'),
		array('lastpost', 'int(10)'),
		array('views', 'int(10)'),
		array('posts', 'int(11)'),
		array('top', 'tinyint(1)'),
		array('typeid1', 'int(10)'),
		array('typeid2', 'int(10)'),
		array('typeid3', 'int(10)'),
		array('typeid4', 'int(10)'),
		array('digest', 'tinyint(3)'),
		array('attachnum', 'tinyint(3)'),
		array('imagenum', 'tinyint(3)'),
		array('modnum', 'tinyint(3)'),
		array('closed', 'tinyint(1)'),
		array('firstpid', 'int(11)'),
		array('status', 'tinyint(1)'),
		array('lastuid', 'int(11)'),
		array('lastusername', 'char(16)'),
	),
	'thread_digest'=>array (
		array('fid', 'smallint(6)'),
		array('tid', 'int(11)'),
		array('digest', 'tinyint(3)'),
	),
	'thread_new'=>array (
		array('fid', 'smallint(6)'),
		array('tid', 'int(11)'),
		array('dateline', 'int(10)'),
		array('lastpost', 'int(10)'),
	),
	'thread_views'=>array (
		array('tid', 'int(11)'),
		array('views', 'int(11)'),
	),
	'post'=>array (
		array('fid', 'smallint(6)'),
		array('pid', 'int(10)'),
		array('tid', 'int(11)'),
		array('uid', 'int(11)'),
		array('dateline', 'int(10)'),
		array('userip', 'int(11)'),
		array('attachnum', 'tinyint(3)'),
		array('imagenum', 'tinyint(3)'),
		array('rates', 'int(11)'),
		array('page', 'smallint(6)'),
		array('username', 'char(16)'),
		array('subject', 'varchar(255)'),
		array('message', 'longtext'),
	),
	'attach'=>array (
		array('fid', 'smallint(6)'),
		array('aid', 'int(10)'),
		array('tid', 'int(11)'),
		array('pid', 'int(11)'),
		array('uid', 'int(10)'),
		array('filesize', 'int(8)'),
		array('width', 'mediumint(8)'),
		array('height', 'mediumint(8)'),
		array('filename', 'char(120)'),
		array('orgfilename', 'char(120)'),
		array('filetype', 'char(7)'),
		array('dateline', 'int(10)'),
		array('comment', 'char(100)'),
		array('downloads', 'int(10)'),
		array('isimage', 'tinyint(1)'),
		array('golds', 'int(10)'),
	),
	'attach_download'=>array (
		array('fid', 'int(10)'),
		array('aid', 'int(10)'),
		array('uid', 'int(10)'),
		array('uploaduid', 'int(10)'),
		array('dateline', 'int(10)'),
		array('golds', 'int(10)'),
	),
	'mypost'=>array (
		array('uid', 'int(11)'),
		array('fid', 'int(11)'),
		array('tid', 'int(11)'),
		array('pid', 'int(11)'),
	),
	'online'=>array (
		array('sid', 'char(16)'),
		array('uid', 'int(11)'),
		array('username', 'char(16)'),
		array('ip', 'int(11)'),
		array('groupid', 'tinyint(3)'),
		array('url', 'char(100)'),
		array('lastvisit', 'int(11)'),
	),
	'pmnew'=>array (
		array('recvuid', 'int(11)'),
		array('senduid', 'int(11)'),
		array('count', 'int(11)'),
		array('dateline', 'int(11)'),
	),
	'pmcount'=>array (
		array('uid1', 'int(11)'),
		array('uid2', 'int(11)'),
		array('count', 'int(11)'),
		array('dateline', 'int(11)'),
	),
	'pm'=>array (
		array('pmid', 'bigint(11)'),
		array('uid1', 'int(11)'),
		array('uid2', 'int(11)'),
		array('uid', 'int(11)'),
		array('page', 'int(11)'),
		array('username1', 'char(16)'),
		array('username2', 'char(16)'),
		array('dateline', 'int(11)'),
		array('message', 'varchar(255)'),
	),
	'follow'=>array (
		array('uid', 'int(11)'),
		array('fuid', 'int(11)'),
		array('direction', 'int(11)'),
	),
	'modlog'=>array (
		array('logid', 'bigint(11)'),
		array('uid', 'int(11)'),
		array('username', 'char(16)'),
		array('fid', 'int(11)'),
		array('tid', 'int(11)'),
		array('pid', 'int(11)'),
		array('subject', 'char(32)'),
		array('comment', 'char(64)'),
		array('credits', 'int(11)'),
		array('golds', 'int(11)'),
		array('dateline', 'int(11)'),
		array('action', 'char(16)'),
	),
	'banip'=>array (
		array('banid', 'bigint(11)'),
		array('ip0', 'smallint(11)'),
		array('ip1', 'smallint(11)'),
		array('ip2', 'smallint(11)'),
		array('ip3', 'smallint(11)'),
		array('uid', 'int(11)'),
		array('dateline', 'int(11)'),
		array('expiry', 'int(11)'),
	),
	'rate'=>array (
		array('rateid', 'bigint(11)'),
		array('uid', 'int(11)'),
		array('username', 'char(16)'),
		array('fid', 'int(11)'),
		array('tid', 'int(11)'),
		array('pid', 'int(11)'),
		array('comment', 'char(64)'),
		array('credits', 'int(11)'),
		array('golds', 'int(11)'),
		array('dateline', 'int(11)'),
		array('ymd', 'int(11)'),
	),
	'stat'=>array (
		array('year', 'int(11)'),
		array('month', 'int(11)'),
		array('day', 'int(11)'),
		array('threads', 'int(11)'),
		array('posts', 'int(11)'),
		array('users', 'int(11)'),
		array('newthreads', 'int(11)'),
		array('newposts', 'int(11)'),
		array('newusers', 'int(11)'),
	),
	'kv'=>array (
		array('k', 'char(32)'),
		array('v', 'text'),
		array('expiry', 'int(11)'),
	),
	'runtime'=>array (
		array('k', 'char(32)'),
		array('v', 'text'),
		array('expiry', 'int(11)'),
	),
);

$db_data = array(
	'group'=> array (
		array('groupid'=>0, 'name'=>'游客组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>0, 'allowpost'=>0, 'allowattach'=>0, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		
		array('groupid'=>1, 'name'=>'管理员组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>10000, 'maxgolds'=>10000, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>1, 'allowdeleteuser'=>1, 'allowviewip'=>1),
		array('groupid'=>2, 'name'=>'超级版主组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>200, 'maxgolds'=>200, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>1, 'allowdeleteuser'=>1, 'allowviewip'=>1),
		array('groupid'=>4, 'name'=>'版主组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>50, 'maxgolds'=>50, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>1, 'allowdeleteuser'=>0, 'allowviewip'=>1),
		array('groupid'=>5, 'name'=>'实习版主组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		
		array('groupid'=>6, 'name'=>'待验证用户组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>0, 'allowpost'=>0, 'allowattach'=>0,  'allowdown'=>0, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>7, 'name'=>'禁止用户组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>0, 'allowthread'=>0, 'allowpost'=>0, 'allowattach'=>0, 'allowdown'=>0, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		
		array('groupid'=>11, 'name'=>'一级用户组', 'creditsfrom'=>0, 'creditsto'=>50, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>12, 'name'=>'二级用户组', 'creditsfrom'=>50, 'creditsto'=>200, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>13, 'name'=>'三级用户组', 'creditsfrom'=>200, 'creditsto'=>1000, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>14, 'name'=>'四级用户组', 'creditsfrom'=>1000, 'creditsto'=>10000, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>15, 'name'=>'五级级用户组', 'creditsfrom'=>10000, 'creditsto'=>10000000, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
	), 
	'user'=> array(
		array('uid'=>1, 'regip'=>'12345554', 'regdate'=>0, 'username'=>'admin', 'password'=>'d14be7f4d15d16de92b7e34e18d0d0f7', 
			'salt'=>'99adde', 'email'=>'admin@admin.com', 'groupid'=>1, 'golds'=>0, 'digests'=>0, 'avatar'=>0, 'threads'=>0, 
			'posts'=>0, 'myposts'=>0, 'credits'=>0, 'follows'=>0, 'followeds'=>0, 
			'newpms'=>0, 'newfeeds'=>0, 'homepage'=>'', 'accesson'=>0, 
			'lastactive'=>0),
		array('uid'=>2, 'regip'=>'12345554', 'regdate'=>0, 'username'=>'系统', 'password'=>'d14be7f4d15d16de92b7e34e18d0d0f7', 
			'salt'=>'99adde', 'email'=>'system@admin.com', 'groupid'=>11, 'golds'=>0, 'digests'=>0, 'avatar'=>0, 'threads'=>0, 
			'posts'=>0, 'myposts'=>0, 'credits'=>0, 'follows'=>0, 'followeds'=>0, 
			'newpms'=>0, 'newfeeds'=>0, 'homepage'=>'', 'accesson'=>0, 
			'lastactive'=>0),
			
	),
	'forum'=> array(
		array('fid'=>1, 'name'=>'默认版块', 'rank'=>0, 'threads'=>0, 'posts'=>0, 'digests'=>0, 'todayposts'=>0, 'lasttid'=>0, 'brief'=>'默认版块介绍', 'accesson'=>0, 'modids'=>'', 'modnames'=>'', 'toptids'=>'', 'orderby'=>0, 'seo_title'=>'', 'seo_keywords'=>''),
	)
);

// 不使用 DB 的 unique 特性，应用程序执行保证 unique
$db_index = array(
	'group'=>array(array('groupid'=>1)),
	'user'=>array(array('uid'=>1), array('username'=>1), array('email'=>1)),
	'user_access'=>array(array('uid'=>1)),
	'forum'=>array(array('fid'=>1)),
	'forum_access'=>array(array('fid'=>1, 'groupid'=>1), array('fid'=>1)),
	'thread_type'=>array(array('fid'=>1), array('typeid'=>1)),
	'thread'=>array(array('fid'=>1, 'lastpost'=>-1), array('fid'=>1, 'tid'=>-1)),
	'thread_type'=>array(array('fid'=>1, 'typeid'=>1)),
	'thread_type_cate'=>array(array('fid'=>1, 'cateid'=>1)),
	'thread_type_count'=>array(array('fid'=>1, 'typeidsum'=>1)),
	'thread_type_data'=>array(array('fid'=>1, 'tid'=>1, 'typeidsum'=>1), array('fid'=>1, 'typeidsum'=>1, 'tid'=>1)),
	'thread_digest'=>array(array('tid'=>-1), array('fid'=>1, 'tid'=>-1)),
	'thread_new'=>array(array('tid'=>-1), array('fid'=>1, 'tid'=>-1), array('lastpost'=>-1)),
	'thread_views'=>array(array('tid'=>1)),
	'post'=>array(array('fid'=>1, 'pid'=>1), array('fid'=>1, 'tid'=>1, 'page'=>1)),
	'attach'=>array(array('aid'=>1), array('fid'=>1, 'tid'=>1), array('fid'=>1, 'pid'=>1), array('uid'=>1, 'isimage'=>1, 'aid'=>1)),
	'attach_download'=>array(array('uid'=>1, 'fid'=>1, 'aid'=>1), array('fid'=>1, 'aid'=>1), array('uploaduid'=>1, 'dateline'=>0)),
	'mypost'=>array(array('uid'=>1, 'fid'=>1, 'pid'=>1), array('uid'=>1, 'fid'=>1, 'tid'=>1), array('uid'=>1, 'pid'=>0)),
	'online'=>array(array('sid'=>1), array('lastvisit'=>1), array('uid'=>1)),
	'pmnew'=>array(array('recvuid'=>1, 'senduid'=>1), array('recvuid'=>1, 'count'=>1)),
	'pmcount'=>array(array('uid1'=>1, 'uid2'=>1)),
	'pm'=>array(array('pmid'=>1), array('uid1'=>1, 'uid2'=>1, 'pmid'=>1)),
	'follow'=>array(array('uid'=>1, 'fuid'=>1), array('uid'=>1), array('fuid'=>1)),
	'modlog'=>array(array('logid'=>1), array('uid'=>1, 'logid'=>1), array('fid'=>1, 'tid'=>1)),
	'banip'=>array(array('banid'=>1), array('ip0'=>1, 'ip1'=>1, 'ip2'=>1, 'ip3'=>1)),
	'rate'=>array(array('rateid'=>1), array('uid'=>1, 'rateid'=>1), array('fid'=>1, 'pid'=>1), array('uid'=>1, 'ymd'=>1)),
	'stat'=>array(array('year'=>1, 'month'=>1, 'day'=>1)),
	'kv'=>array(array('k'=>1)),
	'runtime'=>array(array('k'=>1)),
);