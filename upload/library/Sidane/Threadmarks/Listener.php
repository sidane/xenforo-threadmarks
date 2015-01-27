<?php

class Sidane_Threadmarks_Listener
{
  public static function load_class($class, array &$extend)
  {
    switch($class)
    {
      case 'XenForo_ControllerPublic_Thread':
        $extend[] = 'Sidane_Threadmarks_ControllerPublic_Thread';
        break;
      case 'XenForo_ControllerPublic_Post':
        $extend[] = 'Sidane_Threadmarks_ControllerPublic_Post';
        break;
      case 'XenForo_Model_Post':
        $extend[] = 'Sidane_Threadmarks_Model_Post';
        break;
      case 'XenForo_DataWriter_DiscussionMessage_Post':
        $extend[] = 'Sidane_Threadmarks_DataWriter_Post';
        break;
    }
  }
}