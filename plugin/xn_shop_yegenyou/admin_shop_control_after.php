	// 编辑器依赖上传图片
	public function on_uploadfile() {
		$file = array();
		!empty($_FILES['upfile']) && $file = $_FILES['upfile'];
		!empty($_FILES['Filedata']) && $file = $_FILES['Filedata'];
		empty($file) && $this->message('没有上传文件。', 0);
		
		$uploadpath = $this->conf['upload_path'].'attach_shop/';
		$uploadurl = $this->conf['upload_url'].'attach_shop/';
		
		$goodid = intval(core::gpc('goodid'));
		$seq = intval(core::gpc('seq'));
		$good = $this->shop_good->read($goodid);
		$dateline = empty($good) ? $_SERVER['time'] : $good['dateline'];
		
		empty($good) && $this->message('没有这个商品。', 0);
		
		!is_dir($uploadpath) && mkdir($uploadpath, 0777);
		$diradd = image::set_dir($goodid, $uploadpath);
		
		// 对付一些变态的 iis 环境， is_file() 无法检测无权限的目录。
		$tmpfile = FRAMEWORK_TMP_TMP_PATH.md5(rand(0, 1000000000).$_SERVER['time'].$_SERVER['ip']).'.tmp';
		$succeed = IN_SAE ? copy($file['tmp_name'], $tmpfile) : move_uploaded_file($file['tmp_name'], $tmpfile);
		if(!$succeed) {
			$this->message('移动临时文件错误，请检查临时目录的可写权限。', 0);
		}
		
		$file['tmp_name'] = $tmpfile;
		core::htmlspecialchars($file['name']);
		$filetype = $this->attach->get_filetype($file['name']);
		
		!is_dir($uploadpath) && mkdir($uploadpath, 0777);
		$destext = image::ext($file['name']);
		$fileurl = $diradd.'/'.$goodid.'.'.$destext;
		$thumbfile = $uploadpath.$fileurl;
		
		$good['fileurl'] = $fileurl;
		$this->shop_good->update($good);
		
		copy($file['tmp_name'], $thumbfile);
		is_file($file['tmp_name']) && unlink($file['tmp_name']);
		$s = "<a href=\"$uploadurl$fileurl\" target=\"_blank\"><img src=\"../view/image/filetype/$filetype.gif\" width=\"16\" height=\"16\">$file[name]</a>";
		$this->message($s);
		exit;
	}
	