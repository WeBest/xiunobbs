	public function on_weibologin() {
		$weibologin = $this->kv->get('weibologin');
		$appsecret = $weibologin['appsecret'];
		$appkey = $weibologin['appkey'];
		$callback = DEBUG ? urlencode('http://www.xiuno.com/user-weibotoken.htm') : urlencode('?user-weibotoken.htm');
		
		$state = md5(uniqid(rand(), TRUE)); //CSRF protection
		$login_url = "https://api.weibo.com/oauth2/authorize?response_type=code&client_id=$appkey&redirect_uri=$callback&state=$state&display=";
		header("Location:$login_url");
	}
	
	public function on_weibotoken() {
	
		$weibologin = $this->kv->get('weibologin');
		$appsecret = $weibologin['appsecret'];
		$appkey = $weibologin['appkey'];
		$callback = DEBUG ? urlencode('http://www.xiuno.com/user-weibotoken.htm') : urlencode('?user-weibotoken.htm');
		
		$state = core::gpc('state', 'R');
		$code = core::gpc('code', 'R');
		
		$token_url = "https://api.weibo.com/oauth2/access_token";
		$postStr="grant_type=authorization_code&client_id=$appkey&redirect_uri=$callback&client_secret=$appsecret&code=$code";
		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_URL, $token_url);//设置链接
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息
		curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);//POST数据
		$s = curl_exec($ch);//接收返回信息
		if(curl_errno($ch)) {
			throw new Exception('Errno'.curl_error($ch));//捕抓异常
		}
		if(!$s) {
			curl_close($ch);
			return false;
		}
		
		curl_close($ch);
		$params = json_decode($s,true);
		if(empty($params["access_token"])) {
			throw new Exception('读取access_token 出错。'.$s);
		}
	
		// token 有效期三个月
		$token = $params["access_token"];
		
		// 获取 openid
		$openid = $this->weibologin_get_openid_by_token($token);
		
		// 查询数据表，
		$this->user_weibologin = core::model($this->conf, 'user_weibologin', 'uid', 'uid');
		$arrlist = $this->user_weibologin->index_fetch(array('openid'=>$openid), array(), 0, 1);
		$arr = array_pop($arrlist);
		if(empty($arr)) {
			// 自动注册账户，如果用户名没被注册，则直接生成用户名，完成登录
			if(DEBUG) {
				$weibouser = array('nickname'=>'张三', 'figureurl_1'=>'http://www.baidu.com/img/baidu_jgylogo3.gif', 'figureurl_2'=>'http://www.baidu.com/img/baidu_jgylogo3.gif');
			} else {
				$weibouser = $this->weibologin_get_user_by_openid($openid, $token);
			}
			$username = $weibouser['name'];
			$figureurl_weibo_2 = $weibouser['profile_image_url'];
			if(!$this->user->check_username_exists($username) && !$this->user->check_username($username)) {
				$this->weibo_create_user($username, $figureurl_weibo_2, $openid);
				$url = core::gpc('HTTP_REFERER', 'S') ? core::gpc('HTTP_REFERER', 'S') : './';
				header("Location:$url");
			} else {
				// 新用户名
				$args = encrypt("$openid\t$token", $this->conf['auth_key']);
				$url = "?user-weiboreg-args-$args.htm";
				header("Location:$url");
			}
		} else {
			// 登陆成功，设置 cookie
			$user = $this->user->read($arr['uid']);
			$this->check_user_exists($user);
			$this->user->set_login_cookie($user);
			$url = "./";
			header("Location:$url");
		}
		
	}
	
	public function on_weiboreg() {
		$weibologin = $this->kv->get('weibologin');
		$appsecret = $weibologin['appsecret'];
		$appkey = $weibologin['appkey'];
		
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
				$weibouser = array('nickname'=>'张三', 'figureurl_1'=>'http://www.baidu.com/img/baidu_jgylogo3.gif', 'figureurl_2'=>'http://www.baidu.com/img/baidu_jgylogo3.gif');
			} else {
				$weibouser = $this->weibologin_get_user_by_openid($openid, $token);
			}
			$username = $weibouser['name'];
			$avatar_url_1 = $weibouser['profile_image_url'];
			$avatar_url_2 = $weibouser['avatar_large'];
			$error['username'] = $this->user->check_username($username);
		// 头像
		} else {
			$username = core::gpc('username', 'P');
			$avatar_url_1 = core::gpc('avatar_url_1', 'P');
			$avatar_url_2 = core::gpc('avatar_url_2', 'P');
			
			$conf = $this->conf;
			
			if($avatar_url_2 && !check::is_url($avatar_url_2)) {
				$this->message('avatar_url_2 格式有误');
			}
			
			$error['username'] = $this->user->check_username($username) OR $error['username'] = $this->user->check_username_exists($username);
			if(!array_filter($error)) {
				$this->weibo_create_user($username, $avatar_url_2, $openid);
			}
		}
		
		// 筛选用户名, 用户名，提示是否被注册
		
		$this->view->assign('username', $username);
		$this->view->assign('avatar_url_1', $avatar_url_1);
		$this->view->assign('avatar_url_2', $avatar_url_2);
		$this->view->assign('args', $args);
		$this->view->assign('input', $input);
		$this->view->assign('error', $error);
		$this->view->display('xn_weibo_login_reg.htm');
	}
	
	private function weibologin_get_openid_by_token($token) {
		$url = "https://api.weibo.com/2/account/get_uid.json?access_token={$token}";
		$s = misc::https_fetch_url($url);
		$arr = core::json_decode($s);
		if (isset($arr['error_code'])&&$arr['error_code'] > 0) {
			$error = $arr['error'];
			throw new Exception($error);
		}
		return $arr['uid'];
	}
	
	private function weibologin_get_user_by_openid($openid, $token) {
		$url = "https://api.weibo.com/2/users/show.json?access_token={$token}&uid={$openid}";
		$s = misc::https_fetch_url($url);
		$arr = core::json_decode($s);
		return $arr;
	}
	
	
	private function weibo_create_user($username, $avatar_url_2, $openid) {
		$conf = $this->conf;
		$groupid = 11;
		$salt = rand(100000, 999999);
		$password = ''; // 密码为空，第一次修改，不需要输入密码。
		$email = '';	// email 为空
		$user = array(
			'username'=>$username,
			'email'=>$email,
			'password'=>$password,
			'groupid'=>$groupid,
			'salt'=>$salt,
		);
		
		$uid = $this->user->xcreate($user);
		
		$this->user_weibologin = core::model($this->conf, 'user_weibologin', 'uid', 'uid');
		$this->user_weibologin->create(array('uid'=>$uid, 'openid'=>$openid));
		
		// hook user_create_after.php
		
		$userdb = $this->user->read($uid);
		$this->user->set_login_cookie($userdb);
		
		$this->runtime->xset('users', '+1');
		$this->runtime->xset('todayusers', '+1');
		$this->runtime->xset('newuid', $uid);
		$this->runtime->xset('newusername', $userdb['username']);
		
		// hook user_create_succeed.php
		// 更新头像
		/*
		if($avatar_url_2) {
			$dir = image::get_dir($uid);
			$smallfile = $conf['upload_path']."avatar/$dir/{$uid}_small.gif";
			$middlefile = $conf['upload_path']."avatar/$dir/{$uid}_middle.gif";
			$bigfile = $conf['upload_path']."avatar/$dir/{$uid}_big.gif";
			$hugefile = $conf['upload_path']."avatar/$dir/{$uid}_huge.gif";
			
			
			try {
				$s = misc::fetch_url($avatar_url_2, 5);
				file_put_contents($bigfile, $s);
				image::thumb($bigfile, $smallfile, $conf['avatar_width_small'], $conf['avatar_width_small']);
				image::thumb($bigfile, $middlefile, $conf['avatar_width_middle'], $conf['avatar_width_middle']);
				image::thumb($bigfile, $bigfile, $conf['avatar_width_big'], $conf['avatar_width_big']);
				image::thumb($bigfile, $hugefile, $conf['avatar_width_huge'], $conf['avatar_width_huge']);
				$user['avatar'] = $_SERVER['time'];
			} catch (Exception $e) {
				$userdb['avatar'] = 0;
			}
			$this->user->update($userdb);
		}
		*/
		
	}
