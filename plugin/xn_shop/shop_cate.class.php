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
		misc::arrlist_change_key($catelist, 'rank');
		return $catelist;
	}
	
	public function format(&$cate) {
		
	}
}
?>