global $bbsconf;
$conf = empty($bbsconf) ? $this->conf : $bbsconf;
$fileurl = $good['fileurl'];
$filetype = $this->attach->get_filetype($good['fileurl']);
if($good['fileurl']) {
	$good['fileurl_fmt'] = $conf['upload_url'].'attach_shop/'.$fileurl;
	$good['fileurl_html'] = "<a href=\"$good[fileurl_fmt]\" target=\"_blank\"><img src=\"".$conf['static_url']."view/image/filetype/$filetype.gif\" width=\"16\" height=\"16\" >$fileurl</a>";
	$good['fileurl_download'] = "<a href=\"$good[fileurl_fmt]\" target=\"_blank\"><img src=\"".$conf['static_url']."view/image/filetype/$filetype.gif\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-top: -5px;\">点击下载</a>";
} else {
	$good['fileurl_html'] = '';
	$good['fileurl_download'] = '';
}