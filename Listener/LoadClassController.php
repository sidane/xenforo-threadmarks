<?php

class Sidane_Threadmarks_Listener_LoadClassController
{
  public static function loadClassController($class, array &$extend)
  {
    if ($class == 'XenForo_ControllerPublic_Thread')
    {
      $extend[] = 'Sidane_Threadmarks_ControllerPublic_Thread';
    } elseif ($class == 'XenForo_ControllerPublic_Post') {
      $extend[] = 'Sidane_Threadmarks_ControllerPublic_Post';
    }
  }
}
