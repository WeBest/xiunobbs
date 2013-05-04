<?php
	public function on_qqlogin() {
		$qqlogin = $this->kv->get('qqlogin');
		$appid = $qqlogin['appid'];
		$appkey = $qqlogin['appkey'];
		$callback = urlencode('?user-qqtoken.htm');
		
		$scope = "get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo";
		$state = md5(uniqid(rand(), TRUE)); //CSRF protection
		$login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=$appid&redirect_uri=$callback&state=$state&scope=$scope";
		header("Location:$login_url");
	}
	
	public function on_qqtoken() {
	
		$qqlogin = $this->kv->get('qqlogin');
		$appid = $qqlogin['appid'];
		$appkey = $qqlogin['appkey'];
		$callback = urlencode('?user-qqtoken.htm');
		
		$state = core::gpc('state', 'R');
		$code = core::gpc('code', 'R');
		
		$token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=$appid&redirect_uri=$callback&client_secret=$appkey&code=$code";
		$s = misc::https_fetch_url($token_url);
		if(strpos($s, "callback") !== false) {
			$lpos = strpos($s, "(");
			$rpos = strrpos($s, ")");
			$s  = substr($s, $lpos + 1, $rpos - $lpos -1);
			$arr = core::json_decode($s);
			if(isset($arr['error'])) {
				$error = $arr['error'].'<br />'.$arr['error_description'];
				throw new Exception($error);
			}
		}
	
		$params = array();
		parse_str($s, $params);
		
		if(empty($params["access_token"])) {
			throw new Exception('access_token 解码出错。');
		}
	
		// token 有效期三个月
		$token = $params["access_token"];
		
		// 获取 openid
		$openid = $this->qqlogin_get_openid_by_token($token);
		
		/*
		Array
		(
		    [access_token] => F6890DF038193C8CEB040F2344592714
		    [expires_in] => 7776000
		)
		openid: 6AD06D578F81042387C7F7BFD6D99E38 Array
		(
		    [ret] => 0
		    [msg] => 
		    [nickname] => 黄
		    [gender] => 男
		    [figureurl] => http://qzapp.qlogo.cn/qzapp/100287386/6AD06D578F81042387C7F7BFD6D99E38/30
		    [figureurl_1] => http://qzapp.qlogo.cn/qzapp/100287386/6AD06D578F81042387C7F7BFD6D99E38/50
		    [figureurl_2] => http://qzapp.qlogo.cn/qzapp/100287386/6AD06D578F81042387C7F7BFD6D99E38/100
		    [figureurl_qq_1] => http://q.qlogo.cn/qqapp/100287386/6AD06D578F81042387C7F7BFD6D99E38/40
		    [figureurl_qq_2] => http://q.qlogo.cn/qqapp/100287386/6AD06D578F81042387C7F7BFD6D99E38/100
		    [is_yellow_vip] => 0
		    [vip] => 0
		    [yellow_vip_level] => 0
		    [level] => 0
		    [is_yellow_year_vip] => 0
		)
		*/
		
		// 查询数据表，
		$this->user_qqlogin = core::model($this->conf, 'user_qqlogin', 'uid', 'uid');
		$arrlist = $this->user_qqlogin->index_fetch(array('openid'=>$openid), array(), 0, 1);
		$arr = array_pop($arrlist);
		if(empty($arr)) {
			// 注册或者绑定账号
			$args = encrypt("$openid\t$token", $this->conf['auth_key']);
			$url = "?user-qqreg-args-$args.htm";
			header("Location:$url");
		} else {
			// 登陆成功，设置 cookie
			$user = $this->user->read($arr['uid']);
			$this->check_user_exists($user);
			$this->user->set_login_cookie($user);
			
		}
		
		// 判断是否已经注册
		print_r($qquser);
		
	}
	
	public function on_qqreg() {
		$args = core::gpc('args');
		$s = decrypt($args, $this->conf['auth_key']);
		$arr = explode("\t", $s);
		if(DEBUG) {
			$openid = $token = '';
		} else {
			if(count($arr) < 2) {
				$this->message('参数错误', 0);
			}
			list($openid, $token) = $arr;
		}
		
		$input = $error = array();
		if(!$this->form_submit()) {
			if(DEBUG) {
				$qquser = array('nickname'=>'张三', 'figureurl_1'=>'http://www.baidu.com/img/baidu_jgylogo3.gif', 'figureurl_2'=>'http://www.baidu.com/img/baidu_jgylogo3.gif');
			} else {
				$qquser = $this->qqlogin_get_user_by_openid($openid, $token, $appid);
			}
			$username = $qquser['nickname'];
			$avatar_url_1 = $qquser['figureurl_1'];
			$avatar_url_2 = $qquser['figureurl_2'];
			$email = '';
			$password = '';
			$password2 = '';
			// 头像
		} else {
			print_r($_POST);
			$username = core::gpc('username', 'P');
			$avatar_url_1 = core::gpc('avatar_url_1', 'P');
			$avatar_url_2 = core::gpc('avatar_url_2', 'P');
			$email = core::gpc('email', 'P');
			$password = core::gpc('password', 'P');
			$password2 = core::gpc('password2', 'P');
			
			// 检查参数
			if($avatar_url_1 && !check::is_url($avatar_url_1)) {
				$this->message('avatar_url_1 格式有误');
			}
			
			if($avatar_url_2 && !check::is_url($avatar_url_2)) {
				$this->message('avatar_url_2 格式有误');
			}
			
			$error['email'] = $this->user->check_email($email);
			$error['email_exists'] = $this->user->check_email_exists($email);
			
			// 如果email存在
			if($error['email_exists']) {
				// 如果该Email一天内没激活，则删除掉，防止被坏蛋“占坑”。
				$uid = $this->user->get_uid_by_email($email);
				$_user = $this->user->read($uid);
				if($_user['groupid'] == 6 && $_SERVER['time'] - $_user['regdate'] > 86400) {
					$this->user->xdelete($uid);
					$error['email_exists'] = '';
				}
				$error['email'] = $error['email_exists'];
				unset($error['email_exists']);
			}
			
			$error['username'] = $this->user->check_username($username);
			empty($error['username']) && $error['username'] = $this->user->check_username_exists($username);
			$error['password'] = $this->user->check_password($password);
			$error['password2'] = $this->user->check_password2($password, $password2);
			
			$groupid = $this->conf['reg_email_on'] ? 6 : 11;
			$salt = rand(100000, 999999);
			$user = array(
				'username'=>$username,
				'email'=>$email,
				'password'=>$this->user->md5_md5($password, $salt),
				'groupid'=>$groupid,
				'salt'=>$salt,
			);
			
			// hook user_create_after.php
			
			// 判断结果
			if(!array_filter($error)) {
				$error = array();
				$uid = $this->user->create($user);
				if(empty($uid)) {
					throw new Exception('创建用户失败。');
				}
				$userdb = $this->user->get($uid);
				$this->user->set_login_cookie($userdb);
				
				$this->runtime->xset('users', '+1');
				$this->runtime->xset('todayusers', '+1');
				$this->runtime->xset('newuid', $uid);
				$this->runtime->xset('newusername', $userdb['username']);
				
				// hook user_create_succeed.php
				
				// 更新头像
				if($avatar_url_2) {
					$dir = image::get_dir($uid);
					$smallfile = $conf['upload_path']."avatar/$dir/{$uid}_small.gif";
					$middlefile = $conf['upload_path']."avatar/$dir/{$uid}_middle.gif";
					$bigfile = $conf['upload_path']."avatar/$dir/{$uid}_big.gif";
					
					try {
						$s = misc::get_url($avatar_url_2, 5);
						file_put_contents($bigfile, $s);
						image::thumb($bigfile, $smallfile, $conf['avatar_width_small'], $conf['avatar_width_small']);
						image::thumb($bigfile, $middlefile, $conf['avatar_width_middle'], $conf['avatar_width_middle']);
						image::thumb($bigfile, $bigfile, $conf['avatar_width_big'], $conf['avatar_width_big']);
						$user['avatar'] = $_SERVER['time'];
					} catch (Exception $e) {
						$userdb['avatar'] = 0;
					}
					
					$this->user->update($uid, $userdb);
				}
				
				$this->user_qqlogin = core::model($this->conf, 'user_qqlogin', 'uid', 'uid');
				$this->user_qqlogin->create(array('uid'=>$uid, 'openid'=>$openid));
					
				// hook user_create_succeed.php
			}
		}
		
		// 筛选用户名, 用户名，提示是否被注册
		
		$this->view->assign('username', $username);
		$this->view->assign('password', $password);
		$this->view->assign('password2', $password2);
		$this->view->assign('email', $email);
		$this->view->assign('avatar_url_1', $avatar_url_1);
		$this->view->assign('avatar_url_2', $avatar_url_2);
		$this->view->assign('args', $args);
		$this->view->display('xn_qq_login_reg.htm');
	}
	
	public function on_qqbind() {
		$args = core::gpc('args');
		$s = decrypt($args, $this->conf['auth_key']);
		$arr = explode("\t", $s);
		if(count($arr) < 2) {
			$this->message('参数错误', 0);
		}
		list($openid, $token) = $arr;
		if(!$this->form_submit()) {
			// 筛选用户名
			$this->view->display('xn_qq_login_reg.htm');
		} else {
			// 检查参数
			
			// 注册成功
			
			// 设置 cookies
		}
	}
	
	public function qqlogin_get_openid_by_token($token) {
		$url = "https://graph.qq.com/oauth2.0/me?access_token=$token";
		$s  = misc::https_fetch_url($url);
		if(strpos($s, "callback") !== false) {
			$lpos = strpos($s, "(");
			$rpos = strrpos($s, ")");
			$s  = substr($s, $lpos + 1, $rpos - $lpos -1);
		}
		
		$arr = core::json_decode($s);
		if (isset($arr['error'])) {
			$error = $arr['error'].'<br />'.$arr['error_description'];
			throw new Exception($error);
		}
		
		return $arr['openid'];
	}
	
	public function qqlogin_get_user_by_openid($openid, $token, $appid) {
		$get_user_info = "https://graph.qq.com/user/get_user_info?access_token=$token&oauth_consumer_key=$appid&openid=$openid&format=json";
		$s = misc::https_fetch_url($get_user_info);
		$arr = json_decode($s, true);
		return $arr;
	}
?>