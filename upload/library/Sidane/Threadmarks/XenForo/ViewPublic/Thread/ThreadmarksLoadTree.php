<?php

class Sidane_Threadmarks_XenForo_ViewPublic_Thread_ThreadmarksLoadTree extends XFCP_Sidane_Threadmarks_XenForo_ViewPublic_Thread_ThreadmarksLoadTree
{
  public function renderJson()
  {
    return XenForo_ViewRenderer_Json::jsonEncodeForOutput(array(
      'tree' => $this->_params['tree']
    ));
  }
}
