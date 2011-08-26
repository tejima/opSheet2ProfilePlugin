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
      echo "Member create()\n";
    } 
  }
  public static function member2sheet($virtical = true,$sheetid = 1){
    $spreadsheetService = self::getZend_Gdata_Spreadsheets();
    $obj = Doctrine::getTable('Member')->createQuery('m')->orderBy('m.id DESC')->limit(1)->execute();
    $member_id_max = (int)$obj[0]->id;
    $member_list = Doctrine::getTable('Member')->findAll();
    if($virtical){
      for($i=1;$i<=$member_id_max;$i++){
        $update = $spreadsheetService->updateCell(
                     $i+2,
                     1,
                     $i,
                     opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                     $sheetid
                     );
      }
      foreach($member_list as $member){
        $update = $spreadsheetService->updateCell($member->id+2,
                     2,
                     "{$member->name}",
                     opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                     $sheetid
                     );
      }
    }else{
      for($i=1;$i<=$member_id_max;$i++){
        $update = $spreadsheetService->updateCell(
                     1,
                     $i+2,
                     $i,
                     opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                     $sheetid
                     );
      }
      foreach($member_list as $member){
        $update = $spreadsheetService->updateCell(
                     2,
                     $member->id+2,
                     "{$member->name}",
                     opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                     $sheetid
                     );
      }
    }
    return true;
  }
  public static function community2sheet(){
    $spreadsheetService = self::getZend_Gdata_Spreadsheets();

    $obj = Doctrine::getTable('Community')->createQuery('c')->orderBy('c.id DESC')->limit(1)->execute();
    $community_id_max = (int)$obj[0]->id;
    for($i=1;$i<=$community_id_max;$i++){
      $update = $spreadsheetService->updateCell(
                   1,
                   $i+2,
                   $i,
                   opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                   opConfig::get('opsheetsyncplugin_gapps_sheetid',null)
                   );
    }
    $community_list = Doctrine::getTable('Community')->findAll();
    foreach($community_list as $community){
      $update = $spreadsheetService->updateCell(2,
                   $community->id+2,
                   "{$community->name}",
                   opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                   opConfig::get('opsheetsyncplugin_gapps_sheetid',null));
      $i++;
    }
    return true;
  }
  public static function community_member2sheet(){
    $spreadsheetService = self::getZend_Gdata_Spreadsheets();
    $community_list = Doctrine::getTable('Community')->findAll();
    foreach($community_list as $community){
      $cm_list = Doctrine::getTable('CommunityMember')->findByCommunityId($community->id);
      foreach($cm_list as $cm){
        $update = $spreadsheetService->updateCell(
                     $cm->member_id+2,
                     $cm->community_id+2,
                     'T',
                     opConfig::get('opsheetsyncplugin_gapps_sheetkey',null),
                     opConfig::get('opsheetsyncplugin_gapps_sheetid',null)
                     );
      }
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
      $assoc_line = array_combine($line_header,$line);
      //print_r($assoc_line);
      foreach($assoc_line as $key => $value){
        switch($key){
          case "member_id":
          case "name":
          case "pc_address":
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
  public static function member2profile($member = null){
    if(!$member["member_id"]){ //create member 
      $obj = new Member();
      $obj->is_active = 1;
      $obj->invite_member_id = 1;
      $obj->save();
      $member["member_id"] = $obj->id;
      echo "NEW MEMBER CREATED!!!\n";
    }else{ //update member
      $obj = Doctrine::getTable("Member")->find($member["member_id"]);
      if(!$obj){ //invalid member_id. skip updating profiles.
        return; //avoid member update.
      }
    }
    $obj->name = $member["name"];
    $obj->setConfig("pc_address",$member["pc_address"]);
    $obj->save();

    //FIXME enable text type profile update.
    foreach($member["profile"] as $key => $value){
      $profile = Doctrine_Query::create()->from("Profile p")->where("p.name = ?",$key)->fetchOne();
      if(!$profile){ //プロフィール名がなければスキップ
        echo "NO PROFILE MATCHED. HEADER TEXT YOU PUT IS MAYBE INCORRECT. \n";
        continue; //goto next loop
      }
      $mp = Doctrine_Query::create()->from("MemberProfile mp")->where("mp.member_id = ?",$member["member_id"])->addWhere("mp.profile_id = ?",$profile->id)->fetchOne();
      /////////////////////////////////////////////////////////
      //タイプ判定 テキスト入力型ならそのままmember_profileをアップデートする。
      if($profile["form_type"] == "textarea"){ //text value profile. not option.
        echo "FORM_TYPE == TEXTAREA.\n";
        if(!$mp){ //create
          $mp = new MemberProfile();
        }
        $mp->member_id = $member["member_id"];
        $mp->profile_id = $profile->id;
        $mp->value = $value;
        $mp->save();
        continue; //goto next loop
      }
      echo "FORM_TYPE == SELECT.\n";
      /////////////////////////////////////////////////////////
      //FIXME 複数選択肢の場合の入力方法に対応。現在は単一選択のみ対応。 
      echo $profile->name . "=" . $profile->id;
      echo "\n";
      echo $key . "=>" . $value;
      echo "\n";

      $po = Doctrine_Query::create()->from("ProfileOption po,po.Translation t")->where("po.profile_id = ?",$profile->id)->andWhere("t.value = ?",$value)->fetchOne();
      echo "member_id = " . $member["member_id"] . "\n";
      echo "profile_id = " . $profile->id . "\n";
      echo "po_id = " . $po->id . "\n";
      if(!$mp){ //create it.
        $mp = new MemberProfile();
      }
      $mp->member_id = $member["member_id"];
      $mp->profile_id = $profile->id;
      $mp->profile_option_id = $po->id;
      $mp->save();
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
