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
  public function executeIndex(sfWebRequest $request)
  {
    $this->form = new opSheet2ProfilePluginConfigForm();
    if ($request->isMethod(sfWebRequest::POST))
    {
      $this->form->bind($request->getParameter('s2p'));
      if ($this->form->isValid())
      {
        $this->form->save();
        $this->redirect('opSheet2ProfilePlugin/index');
      }
    }
  }
}


