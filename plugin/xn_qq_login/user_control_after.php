<?
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
		$qquser = $this->qqlogin_get_user_by_openid($openid, $token, $appid);
	} else {
		// 登陆成功
		$user = $this->user->read($arr['uid']);
		
	}
	
	// 判断是否已经注册
	print_r($qquser);
	
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