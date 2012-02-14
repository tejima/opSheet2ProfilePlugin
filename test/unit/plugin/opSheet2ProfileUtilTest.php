<?php

include(dirname(__FILE__).'/../../bootstrap/unit.php');
include(dirname(__FILE__).'/../../bootstrap/database.php');


//INIT
$configuration = ProjectConfiguration::getApplicationConfiguration("pc_frontend", 'test', isset($debug) ? $debug : true);
sfContext::createInstance($configuration);
//INIT

$t = new lime_test(null, new lime_output_color());


//$conn->beginTransaction(); $ua = new opBrowser(); //SETUP

$member_list = SheetSyncUtil::csv2member_list();

$t->is(sizeof($member_list),2,'サイズは2');
$t->is($member_list[0]["Member"]["name"],"Tenchi Tennou",'name match');
$t->is($member_list[1]["Member"]["name"],"Jitou Tennou",'name match');

//$conn->rollback(); //TEARDOWN
//$conn->beginTransaction(); $ua = new opBrowser(); //SETUP

$member_list = SheetSyncUtil::csv2member_list();

foreach($member_list as $line){
  SheetSyncUtil::member2profile($line);
}

$member = Doctrine::getTable("Member")->find(1);
$t->is($member->name,"Tenchi Tennou","Member.nameはTenchi Tennou");
$t->is($member->getConfig("pc_address"),"1@example.com","pc_addressは1@example.com");
$t->is($member->getConfig("password"),md5("password"),"password = 'password'");
$t->is($member->getProfile("op_preset_sex")->value,"Man","op_preset_sex = Man");
$t->is($member->getProfile("op_preset_self_introduction")->value,'秋の田の かりほの庵の 苫をあらみ わが衣手は 露にぬれつつ',"intro = 秋の田の");
$t->is($member->getProfile("op_preset_birthday")->value,"1988-04-23","birthday = 1988-04-23");
$t->is($member->getProfile("op_preset_region")->value,"Tokyo","op_preset_region = Tokyo");

$member = Doctrine::getTable("Member")->find(2);
$t->is($member->name,"Jitou Tennou","Member.nameは");

//$conn->rollback(); //TEARDOWN


$result = $t->to_array();
if(sizeof($result[0]["stats"]["failed"]) !=0)
{
  exit(1);
}else{
  exit(0);
}
