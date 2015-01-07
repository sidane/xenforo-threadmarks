<?php

class Sidane_Threadmarks_Listener_LoadClassModel
{

  public static function loadClassModel($class, array &$extend)
  {
    if ($class == 'XenForo_Model_Post')
    {
      $extend[] = 'Sidane_Threadmarks_Model_Post';
    }
  }

}
