<?php

class Sidane_Threadmarks_Deferred_Cache extends XenForo_Deferred_Abstract
{
  public function execute(array $deferred, array $data, $targetRunTime, &$status)
  {
    $data = array_merge(
      array(
        'batch'    => 100,
        'position' => 0,
        'count'    => 0,
        'resync'   => true,
      ),
      $data
    );
    $startTime = microtime(true);
    $resync = !empty($data['resync']);

    /** @var Sidane_Threadmarks_Model_Threadmarks */
    $threadmarksModel = XenForo_Model::create(
      'Sidane_Threadmarks_Model_Threadmarks'
    );

    $threadIds = $threadmarksModel->getThreadIdsWithThreadMarks(
      $data['position'],
      $data['batch']
    );

    if (empty($threadIds))
    {
      return false;
    }

    foreach ($threadIds as $threadId)
    {
      $threadmarksModel->rebuildThreadmarkCache($threadId, $resync);

      $data['position'] = $threadId;
      $data['count']++;

      if ($targetRunTime)
      {
        $runTime = microtime(true) - $startTime;

        if ($runTime > $targetRunTime)
        {
          break;
        }
      }
    }

    $actionPhrase = new XenForo_Phrase('rebuilding');
    $typePhrase = new XenForo_Phrase('sidane_threadmarks_cache');
    $status = sprintf(
      '%s... %s (%s)',
      $actionPhrase,
      $typePhrase,
      XenForo_Locale::numberFormat($data['count'])
    );

    return $data;
  }

  public function canCancel()
  {
    return true;
  }
}
