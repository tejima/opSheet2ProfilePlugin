<?php
class CreateMemberTask extends sfBaseTask
{
  protected function configure()
  {
    set_time_limit(120);
    mb_language("Japanese");
    mb_internal_encoding("utf-8");
    $this->namespace        = 'zuniv.us';
    $this->name             = 'CreateMember';
    $this->aliases          = array('zu-cm');
    $this->briefDescription = '';

  }
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);
    $list = SheetSyncUtil::createMember(100);
    //SheetSyncUtil::member_list2profile($list);
    //self::processRSS();
  }
}
