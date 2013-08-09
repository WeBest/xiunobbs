
$this->cms_channel = core::model($this->conf, 'cms_channel', array('channelid'), 'channelid');
$this->conf['channellist'] = $channellist = $this->cms_channel->index_fetch(array(), array(), 0, 20);

misc::arrlist_multisort($channellist, 'rank', TRUE);
$this->view->assign('channellist', $channellist);