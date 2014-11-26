<?php

class Sidane_Threadmarks_Listener_InitDependencies
{

  public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
  {
    XenForo_Template_Helper_Core::$helperCallbacks += array(
      'render_threadmark_flag' => array('Sidane_Threadmarks_Helper_Threadmark', 'renderThreadmarkFlag')
    );
  }

}
