<?php
class SheetSyncUtil
{
  public static function createMember($num = 1){
    for($i=0;$i<$num;$i++){
      //FIXME ちゃんとしたメンバーデータを作成する
      $m = new Member();
      $m->name = "";
      $m->is_active = 1;
      $m->invite_member_id = 1;
      $m->save();
      echo "Member created.\n";
    } 
  }
  private static function getZend_Gdata_Spreadsheets($_id = null,$_pass = null){
    $id = Doctrine::getTable('SnsConfig')->get('opsheetsyncplugin_gapps_id',$_id);
    $pass = Doctrine::getTable('SnsConfig')->get('opsheetsyncplugin_gapps_password',$_pass);
    $service = Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
    $client = Zend_Gdata_ClientLogin::getHttpClient($id, $pass, $service);
    return new Zend_Gdata_Spreadsheets($client);
  }
  public static function community_sync(){
    echo "community_sync\n";
    $spreadsheetService = self::getZend_Gdata_Spreadsheets();
    $query = new Zend_Gdata_Spreadsheets_ListQuery();
    $query->setSpreadsheetKey(opConfig::get('opsheetsyncplugin_gapps_sheetkey',null));
    $query->setWorksheetId(opConfig::get('opsheetsyncplugin_gapps_sheetid',null));

    $listfeed = $spreadsheetService->getListFeed($query);
    $community_label_line = $listfeed->entries[0]->getCustom();
    $skip_col = array();
    for($x=2;$x<sizeof($community_label_line);$x++){
      if($community_label_line[$x]->getText() == ""){
        echo "SKIP\n";
        $skip_col[] = $x;
      }else{
        echo "community_label:{$community_label_line[$x]->getText()}\n";
      }
    }
    for($i=1;$i<sizeof($listfeed->entries);$i++){
      $line = $listfeed->entries[$i]->getCustom();
      if($line[1]->getText() == ""){
        continue;
      }
      for($j=2;$j<sizeof($line);$j++){
        if(in_array($skip_col,$j)){
          //blank community
          continue;
        }
        $member_id = $i;
        $community_id = $j-1;
        echo "value:{$line[$j]->getText()} mid:{$member_id} cid:{$community_id}\n";
        if($line[$j]->getText() == "T"){
          echo "Member.id = " . $member_id . " is a member of Community.id = ". $community_id ."\n";
          $q = Doctrine::getTable('CommunityMember')->createQuery('cm')->where('cm.member_id = ?',$member_id)->andWhere('cm.community_id = ?',$community_id);
          $count = $q->count();
          echo "count " . $count;
          if($count == 1){
            //skip
          }else{
            $obj = new CommunityMember();
            $obj->setMemberId($member_id);
            $obj->setCommunityId($community_id);
            $obj->save();
            echo "save it " . $count;
          }
        }else{//brank remove from commu
           echo "Member.id = " . $member_id . " is NOT a member of Community.id = ". $community_id ."\n";
          $q = Doctrine::getTable('CommunityMember')->createQuery('cm')->where('cm.member_id = ?',$member_id)->andWhere('cm.community_id = ?',$community_id);
          $count = $q->count();
          if($count == 1){
            //remove it
            $deleted = Doctrine_Query::create()->delete()
              ->from('CommunityMember cm')
              ->where('cm.member_id = ?', $member_id)
              ->andWhere('cm.community_id = ?', $community_id)
              ->execute();
          }
        }
        $counter++;
      }
    }
  }
  public static function csv2member_list(){
    $csv = Doctrine::getTable("SnsConfig")->get("zuniv_us_sheet2profile_csv");
    $_arr = explode("\n",$csv);
    $line_header = str_getcsv(array_shift($_arr)); 
    $line_list = $_arr; 
    $result = array();
    $profile = array();
    foreach($line_list as $line){
      $line = str_getcsv($line);
      if(preg_match("/^[0-9]+/",$line[0])){
        for($i=0;$i<sizeof($line_header);$i++){
          list($model,$col) = explode(".",$line_header[$i]);
          $result[$model][$col] = $line[$i];
        }
        $result_list[] = $result;
      }
    }
    return $result_list;
  }
  public static function sheet2member_list(){
    $id = Doctrine::getTable("SnsConfig")->get("zuniv_us_googleid");
    $pass = Doctrine::getTable("SnsConfig")->get("zuniv_us_googlepass");
    $sheetid = Doctrine::getTable("SnsConfig")->get("zuniv_us_sheet2profile_sheetid");
    $spreadsheetService = self::getZend_Gdata_Spreadsheets($id,$pass); 
    $query = new Zend_Gdata_Spreadsheets_ListQuery();
    $query->setSpreadsheetKey($sheetid); 
    $query->setWorksheetId(1);
    $listfeed = $spreadsheetService->getListFeed($query);
    $result_list = array();
    foreach($listfeed->entries as $entry){
      $line_list = $entry->getCustom();
      $result = array();
      $profile = array();
      foreach($line_list as $line){
        $key = str_replace("-", "_", $line->getColumnName());
        $value = $line->getText();
        echo $key. " = " . $value;
        echo "\n";
        switch($key){
          case "member_id":
          case "name":
          case "pc_address":
          case "password":
            $result[$key] = $value;
            break;
          default:
            $profile[$key] = $value;
        }
      }
      $result["profile"] = $profile;
      $result_list[] = $result;
    }
    print_r($result_list);

    return $result_list;
  }
  public static function member2profile($m = null){
    if(!$m["Member"]["id"]){ //create member 
      $obj = new Member();
      $obj->is_active = 1;
      $obj->invite_member_id = 1;
      $obj->save();
      $m["Member"]["id"] = $obj->id;
      echo "NEW MEMBER CREATED!!!\n";
    }else{ //update member
      $obj = Doctrine::getTable("Member")->find($m["Member"]["id"]);
      if(!$obj){ //invalid member_id. skip updating profiles.
        return; //avoid member update.
      }
    }
    foreach($m["Member"] as $key => $value){
      $obj->$key = $value;
    }
    $obj->save();
    foreach($m["MemberConfig"] as $key => $value){
      $obj->setConfig($key,$value);
    }

    $member = Doctrine::getTable('Member')->find($m["Member"]["id"]);

    $profileForm = new MemberProfileForm(array(), array(), false);
    $profileForm->setConfigWidgets();
    $v = array();
    $profiles = $member->getProfiles();
    foreach ($profiles as $profile)
    {
      $key = $profile->getName();
      $profileValue = $profile->getValue();
      $v[$key]['value'] = $profileValue;
    }
    $values = $m["MemberProfile"];
    unset($values['memberId']);
    foreach ($values as $key => $value)
    {
      $v[$key]['value'] = $value;
    }

    $profileForm->bind($v);
    if ($profileForm->isValid())
    {
      $profileForm->save($m["Member"]["id"]);
    }
  }
  public static function sheet2friend(){
    echo "sheet2friend START\n";
    $spreadsheetService = self::getZend_Gdata_Spreadsheets();
    $query = new Zend_Gdata_Spreadsheets_ListQuery();
    $query->setSpreadsheetKey(opConfig::get('opsheetsyncplugin_gapps_sheetkey',null));
    $query->setWorksheetId(2);

    $listfeed = $spreadsheetService->getListFeed($query);
    $friend_label_line = $listfeed->entries[0]->getCustom();
    $skip_col = array();

    //欠番のスキップすべきカラムを探す
    for($x=2;$x<sizeof($friend_label_line);$x++){
      if($friend_label_line[$x]->getText() == ""){
        echo "SKIP\n";
        $skip_col[] = $x;
      }else{
        echo "friend_label:{$friend_label_line[$x]->getText()}\n";
      }
    }
    for($i=1;$i<sizeof($listfeed->entries);$i++){
      $line = $listfeed->entries[$i]->getCustom();
      if($line[1]->getText() == ""){
        continue;
      }
      for($j=2;$j<sizeof($line);$j++){
        if(in_array($skip_col,$j)){
          //blank community
          continue;
        }
        $member_id = $i;
        $community_id = $j-1;
        echo "value:{$line[$j]->getText()} mid:{$member_id} cid:{$community_id}\n";
        if($line[$j]->getText() == "T"){
          echo "Member.id = " . $member_id . " is a member of Community.id = ". $community_id ."\n";
          $q = Doctrine::getTable('CommunityMember')->createQuery('cm')->where('cm.member_id = ?',$member_id)->andWhere('cm.community_id = ?',$community_id);
          $count = $q->count();
          echo "count " . $count;
          if($count == 1){
            //skip
          }else{
            $obj = new CommunityMember();
            $obj->setMemberId($member_id);
            $obj->setCommunityId($community_id);
            $obj->save();
            echo "save it " . $count;
          }
        }else{//brank remove from commu
           echo "Member.id = " . $member_id . " is NOT a member of Community.id = ". $community_id ."\n";
          $q = Doctrine::getTable('CommunityMember')->createQuery('cm')->where('cm.member_id = ?',$member_id)->andWhere('cm.community_id = ?',$community_id);
          $count = $q->count();
          if($count == 1){
            //remove it
            $deleted = Doctrine_Query::create()->delete()
              ->from('CommunityMember cm')
              ->where('cm.member_id = ?', $member_id)
              ->andWhere('cm.community_id = ?', $community_id)
              ->execute();
          }
        }
        $counter++;
      }
    }
  }
  public static function friend2sheet(){
    
    $spreadsheetService = self::getZend_Gdata_Spreadsheets();
    echo "friend2sheet START\n";
    $obj = Doctrine::getTable('Member')->createQuery('m')->orderBy('m.id DESC')->limit(1)->execute();
    $member_id_max = (int)$obj[0]->id;

    for($i=1;$i<=$member_id_max;$i++){
      $member_id = $i;
      $obj = Doctrine::getTable('MemberRelationship')->createQuery('mr')->where("mr.member_id_from = ?",$member_id)->andWhere("mr.member_id_to < ?",$member_id)->execute();
      foreach($obj as $mr){
        echo "{$mr->member_id_from}-{$mr->member_id_to}\n";
        $update = $spreadsheetService->updateCell(
                   $mr->member_id_from + 2,
                   $mr->member_id_to + 2,
                   'T',
                   opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                   2
                   );
      } 
    }
  }
}
