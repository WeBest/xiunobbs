<?php

/*
 * Copyright (C) xiuno.com
 */

class shop_cate extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'shop_cate';
		$this->primarykey = array('cateid');
		$this->maxcol = 'cateid';
	}
	
	public function get_list() {
		$catelist = $this->index_fetch(array(), array(), 0, 1000);
		misc::arrlist_multisort($catelist, 'rank');
		misc::arrlist_change_key($catelist, 'cateid');
		return $catelist;
	}
	
	public function get_arr() {
		$catelist = $this->get_list();
		$arr = misc::arrlist_key_values($catelist, 'cateid', 'name');
		return $arr;
	}
	
	public function xupdate($arr) {
		return $this->update($arr);
	}
	
	public function xdelete($cateid) {
		return $this->delete($cateid);
	}
	
	public function xcreate($arr) {
		$maxcateid = $this->shop_cate->maxid();
		if(isset($arr['cateid']) && $arr['cateid'] >= $maxcateid) {
			$this->shop_cate->maxid($arr['cateid']);
		}
		return $this->create($arr);
	}
	
	public function format(&$cate) {
		
	}
}
?>