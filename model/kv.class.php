<?php

/*
 * Copyright (C) xiuno.com
 */

// 简单方便的 key - value 格式的存储，兼容SAE，便于分布式部署。
// 不过期（如果需要过期机制，执行存入时间戳）
class kv extends base_model {
	
	private $data = array();		// 合并存储
	private $changed = array();		// 合并存储改变标志位
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->table = 'kv';
		$this->primarykey = array('k');
		
		// 开启 memcached 加速此 model
		// $this->conf['cache']['enable'] = 1;
		
		//IN_SAE && $this->conf['db']['type'] = 'saekv';
	}
	
	public function get($k) {
		$arr = parent::get($k);
		return !empty($arr) ? core::json_decode($arr['v']) : FALSE;
	}
	
	public function set($k, $s) {
		$s = core::json_encode($s);
		$arr = parent::get($k);
		$arr['v'] = $s;
		return parent::set($k, $arr);
	}
	
	// 合并读取
	public function xget($key = 'conf') {
		$this->data[$key] = $this->get($key);
		return $this->data[$key];
	}
	
	// 合并写入
	public function xset($k, $v, $key = 'conf') {
		if(empty($this->data[$key])) {
			$this->data[$key] = $this->get($key);
		}
		$this->data[$key][$k] = $v;
		$this->changed[$key] = TRUE;
	}
	
	// 保存
	public function xsave($key = 'conf') {
		$this->set($key, $this->data[$key]);
	}
	
	// 删除一个 key, 
	// $arg2 = FALSE, $arg3 = FALSE, $arg4 = FALSE 仅仅为了兼容 base_model, 没有意义
	public function delete($k, $arg2 = FALSE, $arg3 = FALSE, $arg4 = FALSE) {
		return parent::delete($k);
	}

}
?>