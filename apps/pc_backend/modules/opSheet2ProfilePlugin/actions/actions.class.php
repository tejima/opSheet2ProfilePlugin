<?php

/**
 * opSheet2ProfilePlugin actions.
 *
 * @package    OpenPNE
 * @subpackage opSheet2ProfilePlugin
 * @author     Your name here
 */
class opSheet2ProfilePluginActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfWebRequest $request A request object
  */
  public function executeIndex(sfWebRequest $request)
  {
    $this->forward('default', 'module');
  }
}
