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
	
	public function xcreate($arr) {
		// 判断记录是否存在。 goodid, seq
		$image = $arr['seq'] > 0 ? $this->get_image_by_goodid_seq($arr['goodid'], $arr['seq']) : array();
		if($image) {
			$image['width'] = $arr['width'];
			$image['height'] = $arr['height'];
			$this->update($image);
		} else {
			return $this->create($arr);
		}
		
	}
	
	public function get_list_by_goodid($goodid, $page = 1, $pagesize = 100) {
		$start = ($page - 1) * $pagesize;
		$imagelist = $this->index_fetch(array('goodid'=>$goodid), array(), $start, $pagesize);
		misc::arrlist_change_key($imagelist, 'imageid');
		return $imagelist;
	}
	
	public function get_loop_list($goodid) {
		$imagelist = $this->index_fetch(array('goodid'=>$goodid, 'seq'=>array('>'=>0)), array(), 0, 6);
		misc::arrlist_multisort($imagelist, 'seq');
		$retlist = array();
		$uploadurl = $this->conf['upload_url'].'attach_shop/';
		foreach($imagelist as $k=>$v) {
			if($v['seq'] > 0 && $v['fileurl']) {
				$retlist[] = $uploadurl.$v['fileurl'];
			}
		}
		return $retlist;
	}
	
	public function get_seq($imagelist, $seq, $isthumb = FALSE) {
		global $bbsconf;
		$uploadurl = $this->conf['upload_url'].'attach_shop/';
		foreach($imagelist as $image) {
			if($image['seq'] == $seq) {
				$file = $uploadurl.$image['fileurl'];
				$file = image::thumb_name($file);
				return $file;
			}
		}
		return $bbsconf['static_url'].'/view/image/nopic.gif';
	}
	
	private function get_image_by_goodid_seq($goodid, $seq) {
		$imagelist = $this->index_fetch(array('goodid'=>$goodid, 'seq'=>$seq), array(), 0, 6);
		return current($imagelist);
	}
	
	public function get_cover($goodid) {
		$imglist = $this->get_list_by_goodid($goodid);
		return $this->get_seq($imglist, 1, TRUE);
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
		$thumb2 = image::thumb_name($thumb);
		is_file($file) && unlink($file);
		is_file($thumb) && unlink($thumb);
		is_file($thumb2) && unlink($thumb2);
		return $this->delete($imageid);
	}
}
?>