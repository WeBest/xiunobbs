$this->thread_blog = core::model($this->conf, 'thread_blog', array('fid', 'tid'));

if($isfirst) {
	$arr = $this->thread_blog->read($fid, $tid);
	if($arr) {
		$message = preg_replace('/&nbsp;/', '', $message);
		$arr['brief'] = htmlspecialchars(utf8::substr(strip_tags($message), 0, 140));
		// 图片缩略图
		$uploadpath = $this->conf['upload_path'].'attach/';
		$uploadurl = $this->conf['upload_url'].'attach/';
		$imglist = $this->attach->get_list_by_fid_pid($fid, $pid, $isimage = 1);
		$i = 1;
		foreach($imglist as $k => $v) {
			// 处理文件
			$imginfo = getimagesize($uploadpath.$v['filename']);
			if($imginfo[0] >= 140){
				$width = 140;
			} else {
				$width = $imginfo[0];
			}
			if($imginfo[1] >= 100) {
				$height = 100;
			} else {
				$height = $imginfo[1];
			}
			$thumb = image::safe_thumb($uploadpath.$v['filename'], $v['aid'], '_blog_thumb.jpg', $uploadpath, $width, $height);
			$thumbfile = $uploadpath.$thumb['fileurl'];
			image::clip($thumbfile, $thumbfile, 0, 0, 140, 100);	// 对付金箍棒图片
			if($i == 1) $arr['coverimg'] = $thumb['fileurl'];
			$i++;
		}
		$this->thread_blog->update($arr);
	}
}