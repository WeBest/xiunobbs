
	// 给插件预留个位置
	public function on_index2() {
		
		$this->_checked['index'] = ' class="checked"';
		$this->_checked['bbs'] = '';
		$this->view->display('yegenyou_index2.htm');
	}