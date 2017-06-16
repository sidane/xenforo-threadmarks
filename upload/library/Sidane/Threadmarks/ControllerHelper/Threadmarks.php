<?php

class Sidane_Threadmarks_ControllerHelper_Threadmarks extends XenForo_ControllerHelper_Abstract
{
  public function getRecentThreadmarks(array $thread, array $forum)
  {
    if (empty($thread['threadmark_count']))
    {
      return null;
    }

    $threadmarksModel = $this->_controller->getModelFromCache(
      'Sidane_Threadmarks_Model_Threadmarks'
    );

    if (!$threadmarksModel->canViewThreadmark($thread, $forum))
    {
      return null;
    }

    $fetchOptions = array();
    $menuLimit = $threadmarksModel->getMenuLimit($thread);

    $threadmarkCategories = $threadmarksModel->getRecentThreadmarksByThread(
      $thread,
      $forum,
      $menuLimit,
      0,
      $fetchOptions
    );

    if (empty($threadmarkCategories))
    {
      return null;
    }

    $threadmarkCategoryPositions = $threadmarksModel
      ->getThreadmarkCategoryPositionsByThread($thread);

    foreach ($threadmarkCategories as $threadmarkCategoryId => &$threadmarkCategory)
    {
      if (isset($threadmarkCategoryPositions[$threadmarkCategoryId]))
      {
        $threadmarkCategory['count'] = $threadmarkCategoryPositions[$threadmarkCategoryId] + 1;
      }
      else if (isset($threadmarkCategory['children']) && is_array($threadmarkCategory['children']))
      {
        $threadmarkCategory['count'] = count($threadmarkCategory['children']);
      }
      else
      {
        // data doesn't make any sense, so skip it.
        unset($threadmarkCategories[$threadmarkCategoryId]);
        continue;
      }

      // $menuLimit: 0 = unlimited
      $threadmarkCategory['more_threadmarks'] = false;
      if (($menuLimit > 0) && ($threadmarkCategory['count'] > $menuLimit))
      {
        $threadmarkCategory['more_threadmarks'] = true;
      }
    }

    if (empty($threadmarkCategories))
    {
      return null;
    }

    $loggedIn = (XenForo_Visitor::getUserId() !== 0);
    $hideMenuFromGuests = XenForo_Application::getOptions()
      ->sidaneThreadmarksHideMenuFromGuests;

    return array(
      'threadmark_categories' => $threadmarkCategories,
      'logged_in'             => $loggedIn,
      'hide_menu_from_guests' => $hideMenuFromGuests
    );
  }
}
