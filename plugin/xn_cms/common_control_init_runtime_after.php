
$this->cms_channel = core::model($this->conf, 'cms_channel', array('channelid'), 'channelid');
$this->conf['channellist'] = $this->cms_channel->index_fetch(array(), array(), 0, 20);

misc::arrlist_multisort($this->conf['channellist'], 'rank', TRUE);
$this->view->assign('channellist', $this->conf['channellist']);