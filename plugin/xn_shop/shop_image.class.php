<?php

/*
 * Copyright (C) xiuno.com
 */

class shop_image extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'shop_image';
		$this->primarykey = array('imageid');
		$this->maxcol = 'imageid';
	}
	
	public function get_list_by_goodid($goodid, $page = 1, $pagesize = 100) {
		$start = ($page - 1) * $pagesize;
		$imagelist = $this->index_fetch(array('goodid'=>$goodid), array(), $start, $pagesize);
		misc::arrlist_change_key($imagelist, 'imageid');
		return $imagelist;
	}
	
	public function get_loop_list($goodid) {
		$imagelist = $this->index_fetch(array('goodid'=>$goodid), array(), 0, 6);
		$retlist = array();
		$uploadurl = $this->conf['upload_url'].'attach_shop/';
		foreach($imagelist as $k=>$v) {
			if($v['seq'] >0 && $v['fileurl']) {
				$retlist[$v['imageid']] = $uploadurl.$v['fileurl'];
			}
		}
		return $retlist;
	}
	
	public function get_seq($imagelist, $seq) {
		global $bbsconf;
		$uploadurl = $this->conf['upload_url'].'attach_shop/';
		foreach($imagelist as $image) {
			if($image['seq'] == $seq) {
				return $uploadurl.$image['fileurl'];
			}
		}
		return $bbsconf['static_url'].'/view/image/nopic.gif';
	}
	
	public function get_cover($goodid) {
		$imglist = $this->get_list_by_goodid($goodid);
		$file = $this->get_seq($imglist, 1);
		$file = image::thumb_name($file);
		return $file;
	}
	
	public function delete_by_goodid($goodid) {
		$imglist = $this->get_list_by_goodid($goodid);
		foreach($imglist as $img) {
			$this->xdelete($img['imageid']);
		}
		return count($imglist);
	}
	
	public function xdelete($imageid) {
		$image = $this->read($imageid);
		$file = $this->conf['upload_path'].'attach_shop/'.$image['filepath'];
		$thumb = image::thumb_name($file);
		is_file($file) && unlink($file);
		is_file($thumb) && unlink($thumb);
		return $this->delete($imageid);
	}
}
?>