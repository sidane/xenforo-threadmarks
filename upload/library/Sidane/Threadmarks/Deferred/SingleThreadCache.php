<?php


class Sidane_Threadmarks_Deferred_SingleThreadCache extends XenForo_Deferred_Abstract
{
  public function execute(array $deferred, array $data, $targetRunTime, &$status)
  {
    if (!isset($data['threadId']))
    {
      return false;
    }

    /** @var XenForo_Model_Post */
    $threadMarkModel = XenForo_Model::create('Sidane_Threadmarks_Model_Threadmarks');

    $threadMarkModel->rebuildThreadMarkCache($data['threadId']);


    return false;
  }


  public function canCancel()
  {
    return false;
  }
}