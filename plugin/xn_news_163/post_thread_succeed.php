$fid = $thread['fid'];
$tid = $thread['tid'];
$pid = $thread['firstpid'];
$thread['coverimg'] = '';
if($thread['imagenum'] > 0) {
	$attachlist = $this->attach->index_fetch(array('fid'=>$fid, 'pid'=>$pid), array(), 0, 20);
	foreach($attachlist as $k=>$attach) {
		if($attach['isimage'] == 1) {
			$thread['coverimg'] = image::thumb_name($attach['filename']);
			break;
		}
	}
}

$firstpost = $this->post->read($thread['fid'], $thread['firstpid']);
$message = htmlspecialchars(strip_tags($firstpost['message']));
$message = str_replace(array('&nbsp;', '&amp;'), array(' ', '&'), $message);
$thread['brief'] = utf8::cutstr_cn($message, 200);
$this->thread->update($thread);

