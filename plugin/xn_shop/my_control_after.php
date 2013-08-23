	public function on_order() {
		
		$this->_checked['my_order'] = 'class="checked"';
		$do = core::gpc('do');
		empty($do) && $do = 'list';
		if($do == 'list') {
			$pagesize = 20;
			$page = misc::page();
			$n = $this->shop_order->count();
			$pages = misc::pages("?my-order-do-list.htm", $n, $page, $pagesize);
			$orderlist = $this->shop_order->get_list_by_uid($this->_user['uid'], $page, $pagesize);
			
			$this->view->assign('page', $page);
			$this->view->assign('pages', $pages);
			$this->view->assign('orderlist', $orderlist);
			$this->view->assign('orderlist', $orderlist);
			$this->view->display('my_order_list.htm');
		} elseif($do == 'delete') {
			$orderid = intval(core::gpc('orderid'));
			$order = $this->shop_order->read($orderid);
			empty($order) && $this->message('订单不存在。');
			$order['uid'] != $this->_user['uid'] && $this->message('您无权删除别人的订单。', 0);
			
			$this->shop_order->xdelete($orderid);
			$this->message('删除成功。');
		} elseif($do == 'read') {
			$orderid = intval(core::gpc('orderid'));
			$order = $this->shop_order->read($orderid);
			empty($order) && $this->message('订单不存在。');
			
			$this->shop_order->format($order);
			
			$this->view->assign('orderid', $orderid);
			$this->view->assign('order', $order);
			$this->view->display('my_order_read.htm');
		// 更新订单
		} elseif($do == 'update') {
			$orderid = intval(core::gpc('orderid'));
			$order = $this->shop_order->read($orderid);
			empty($order) && $this->message('订单不存在。');
			$order['uid'] != $this->_user['uid'] && $this->message('您无权更新别人的订单。');
			$order['status'] > 0 && $this->message('订单当前状态不能更新。');
			
			$recv_address = core::gpc('recv_address', 'P');
			$recv_name = core::gpc('recv_name', 'P');
			$recv_mobile = core::gpc('recv_mobile', 'P');
			$recv_comment = core::gpc('recv_comment', 'P');
			
			$order['recv_address'] = $recv_address;
			$order['recv_name'] = $recv_name;
			$order['recv_mobile'] = $recv_mobile;
			$order['recv_comment'] = $recv_comment;
			$this->shop_order->update($order);
			
			$this->message('更新成功');
		}
	}
	