<?php


class Sidane_Threadmarks_Deferred_Cache extends XenForo_Deferred_Abstract
{
  public function execute(array $deferred, array $data, $targetRunTime, &$status)
  {
    $data = array_merge(array(
      'position' => 0,
      'count' => 0,
      'batch' => 100,
    ), $data);
    $s = microtime(true);

    /** @var Sidane_Threadmarks_Model_Threadmarks */
    $threadMarkModel = XenForo_Model::create('Sidane_Threadmarks_Model_Threadmarks');

    $threadIds = $threadMarkModel->getThreadIdsWithThreadMarks($data['position'], $data['batch']);

    if(empty($threadIds))
    {
      return false;
    }

    foreach($threadIds as $threadId)
    {
      $threadMarkModel->rebuildThreadMarkCache($threadId);
      $data['position'] = $threadId;
      $data['count']++;
      if ($targetRunTime && microtime(true) - $s > $targetRunTime)
      {
        break;
      }
    }

    $rbPhrase = new XenForo_Phrase('rebuilding');
    $typePhrase = new XenForo_Phrase('sidane_threadmarks_cache');
    $status = sprintf('%s... %s (%s)', $rbPhrase, $typePhrase, XenForo_Locale::numberFormat($data['count']));  

    return $data;
  }


  public function canCancel()
  {
    return true;
  }
}