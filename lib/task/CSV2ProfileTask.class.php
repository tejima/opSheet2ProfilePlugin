<?php
class CSV2ProfileTask extends sfBaseTask
{
  protected function configure()
  {
    set_time_limit(120);
    mb_language("Japanese");
    mb_internal_encoding("utf-8");
    $this->namespace        = 'zuniv.us';
    $this->name             = 'Csv2Profile';
    $this->aliases          = array('zu-c2p');
    $this->briefDescription = '';

  }
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);
    $list = SheetSyncUtil::csv2member_list();
    SheetSyncUtil::member_list2profile($list);
  }
}
