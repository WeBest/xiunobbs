<?php

/*
 * Copyright (C) xiuno.com
 */

class shop_reply extends base_model {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'shop_reply';
		$this->primarykey = array('replyid');
		$this->maxcol = 'replyid';
	}
	
	public function get_list($goodid, $page = 1) {
		$pagesize = 20;
		$start = ($page - 1) * $pagesize;
		$replylist = $this->index_fetch(array('goodid'=>$goodid), array('replyid'=>1), $start, $pagesize);
		return $replylist;
	}
	
	public function xcreate($arr) {
		$replyid = $this->create($arr);
		$good = $this->shop_good->read($arr['goodid']);
		$good['replies']++;
		$this->shop_good->update($good);
		return $replyid;
	}
	
	public function xdelete($orderid) {
		$n = $this->delete($goodid);
		if($n > 0) {
			$good = $this->shop_good->read($arr['goodid']);
			$good['replies']--;
			$this->shop_good->update($good);
		}
		return $n;
	}
	
	public function delete_by_goodid($goodid) {
		return $this->index_delete(array('goodid'=>$goodid));
	}
	
	public function format(&$shop) {
		
	}
}
?>