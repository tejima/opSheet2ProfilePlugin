<?php

$_env = 'test';

if (!isset($_app))
{
  $_app = 'pc_frontend';
}

if (!isset($fixture))
{
  $fixture = 'common';
}

$configuration = ProjectConfiguration::getApplicationConfiguration($_app, $_env, true);
new sfDatabaseManager($configuration);

$conn = opDoctrineQuery::getMasterConnectionDirect();
$conn->exec('SET FOREIGN_KEY_CHECKS = 0');

$task = new sfDoctrineBuildTask($configuration->getEventDispatcher(), new sfFormatter());
$task->setConfiguration($configuration);
$task->run(array(), array(
  'no-confirmation' => true,
  'db'              => true,
  'and-load'        => dirname(__FILE__).'/../fixtures/'.$fixture,
  'application'     => $_app,
  'env'             => $_env,
));

$conn = Doctrine_Manager::getInstance()->getCurrentConnection();
$conn->clear();
