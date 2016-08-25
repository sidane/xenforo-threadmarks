<?php

class Sidane_Threadmarks_XenForo_Model_Draft extends XFCP_Sidane_Threadmarks_XenForo_Model_Draft
{
  public function saveDraft($key, $message, array $extraData = array(), array $viewingUser = null, $lastUpdate = null)
  {
    if (Sidane_Threadmarks_Globals::$threadmarkLabel)
    {
      $extraData['threadmark'] = Sidane_Threadmarks_Globals::$threadmarkLabel;
    }
    return parent::saveDraft($key, $message, $extraData, $viewingUser, $lastUpdate);
  }
}