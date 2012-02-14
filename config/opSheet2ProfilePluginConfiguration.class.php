<?php

class opSheet2ProfilePluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    sfToolkit::addIncludePath(array(
      OPENPNE3_CONFIG_DIR.'/../lib/vendor/'
    ));

  }
}



