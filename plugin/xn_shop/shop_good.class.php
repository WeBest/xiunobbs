<?php

/*
 * Copyright (C) xiuno.com
 */

class shop_good extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'shop_good';
		$this->primarykey = array('goodid');
		$this->maxcol = 'goodid';
	}
	
	public function get_list_by_cateid($cateid, $page = 1, $pagesize = 20) {
		$start = ($page - 1) * $pagesize;
		$cond = $cateid ? array('cateid'=>$cateid) : array();
		$shoplist = $this->index_fetch($cond, array(), $start, $pagesize);
		misc::arrlist_multisort($shoplist, 'rank');
		misc::arrlist_change_key($shoplist, 'goodid');
		foreach($shoplist as &$shop) {
			$this->format($shop);
		}
		return $shoplist;
	}
	
	public function xcreate($arr) {
		$goodid = $this->create($arr);
		if(!empty($arr['goodid'])) {
			$this->shop_good->maxid('+1');
		}
		if($arr['cateid'] > 0) {
			$cate = $this->shop_cate->read($arr['cateid']);
			$cate['goods']++;
			$this->shop_cate->update($cate);
		}
		return $goodid;
	}
	
	public function xdelete($goodid) {
		$good = $this->read($goodid);
		$n = $this->delete($goodid);
		if($n > 0 && $good['cateid']) {
			$cate = $this->shop_cate->read($good['cateid']);
			$cate['goods']--;
			$this->shop_cate->update($cate);
		}
		
		// 删除回复
		$this->shop_reply->delete_by_goodid($goodid);
		
		// 删除商品附件
		$diradd = image::get_dir($goodid);
		$attachpath = $this->conf['upload_path'].'attach_shop/'.$diradd;
		
		// 遍历该目录下的 shopid_xxx.jpg，最多一千张
		$this->shop_image->delete_by_goodid($goodid);
		
		/*
		$files = misc::scandir($attachpath);
		foreach($files as $file) {
			if(preg_match("#^{$goodid}_#", $file)) {
				is_file($attachpath.$file) && unlink($attachpath.$file);
			}
		}*/
		return $n;
	}
	
	// 判断分类是否改变
	public function xupdate($arr) {
		$good = $this->read($arr['goodid']);
		if(empty($good)) return FALSE;
		if($good['cateid'] != $arr['cateid'] && $good['cateid']) {
			$cate = $this->shop_cate->read($good['cateid']);
			$cate['goods']--;
			$this->shop_cate->update($cate);
			
			$cate = $this->shop_cate->read($arr['cateid']);
			$cate['goods']++;
			$this->shop_cate->update($cate);
		}
		return $this->update($arr);
	}
	
	public function format(&$shop) {
		$shop['dateline_fmt'] = misc::humandate($shop['dateline']);
	}
}
?>