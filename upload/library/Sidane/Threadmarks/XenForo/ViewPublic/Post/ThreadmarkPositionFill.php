<?php

class Sidane_Threadmarks_XenForo_ViewPublic_Post_ThreadmarkPositionFill extends XFCP_Sidane_Threadmarks_XenForo_ViewPublic_Post_ThreadmarkPositionFill
{
  public function renderJson()
  {
    return XenForo_ViewRenderer_Json::jsonEncodeForOutput(array(
      'previousThreadmark' => $this->_params['previousThreadmark'],
      'lastThreadmark' => $this->_params['lastThreadmark']
    ));
  }
}

if (false)
{
  class XFCP_Sidane_Threadmarks_XenForo_ViewPublic_Post_ThreadmarkPositionFill extends XenForo_ViewPublic_Base {}
}
