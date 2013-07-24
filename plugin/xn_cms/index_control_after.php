	public function on_product() {
		$this->_checked['bbs'] = '';
		$this->_checked['index'] = '';
		$this->_checked['cms_product'] = ' class="checked"';
		
		$this->view->display('xn_old_bbs_product.htm');
	}