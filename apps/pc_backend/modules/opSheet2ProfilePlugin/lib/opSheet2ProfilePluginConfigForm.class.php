<?php
class opSheet2ProfilePluginConfigForm extends sfForm
{
  protected $configs = array(
    'googleid' => 'zuniv_us_googleid',
    'googlepass' => 'zuniv_us_googlepass',
    'sheet2profile_sheetid' => 'zuniv_us_sheet2profile_sheetid',
    'sheet2profile_csv' => 'zuniv_us_sheet2profile_csv',
  );
  public function configure()
  {
    $this->setWidgets(array(
      'googleid' => new sfWidgetFormInput(array()),
      'googlepass' => new sfWidgetFormInput(array()),
      'sheet2profile_sheetid' => new sfWidgetFormInput(),
      'sheet2profile_csv' => new sfWidgetFormTextarea(array(), array('rows' => '20', 'cols' => '100')),
    ));
    $this->setValidators(array(
      'googleid' => new sfValidatorString(array(),array()),
      'googlepass' => new sfValidatorString(array(),array()),
      'sheet2profile_sheetid' => new sfValidatorString(array(),array()),
      'sheet2profile_csv' => new sfValidatorString(array(),array()),
    ));
    foreach($this->configs as $k => $v)
    {
      $config = Doctrine::getTable('SnsConfig')->retrieveByName($v);
      if($config)
      {
        $this->getWidgetSchema()->setDefault($k,$config->getValue());
      }
    }
    $this->getWidgetSchema()->setNameFormat('s2p[%s]');
  }
  public function save()
  {
    foreach($this->getValues() as $k => $v)
    {
      if(!isset($this->configs[$k]))
      {
        continue;
      }
      $config = Doctrine::getTable('SnsConfig')->retrieveByName($this->configs[$k]);
      if(!$config)
      {
        $config = new SnsConfig();
        $config->setName($this->configs[$k]);
      }
      $config->setValue($v);
      $config->save();
    }
  }
  public function validate($validator,$value,$arguments = array())
  {
    return $value;
  }
}

