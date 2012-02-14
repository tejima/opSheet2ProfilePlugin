<?php
class CSV2ProfileTask extends sfBaseTask
{
  protected function configure()
  {
    set_time_limit(120);
    mb_language("Japanese");
    mb_internal_encoding("utf-8");
    $this->namespace        = 'zuniv.us';
    $this->name             = 'CSV2Profile';
    $this->aliases          = array('zu-c2p');
    $this->briefDescription = '';
  }
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);
    $member_list = SheetSyncUtil::csv2member_list();
    foreach($member_list as $member){
      SheetSyncUtil::member2profile($member);
    }
  }
}
