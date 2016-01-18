<?php

class Sidane_Threadmarks_ControllerHelper_Threadmarks extends XenForo_ControllerHelper_Abstract
{

  public function getRecentThreadmarks(array $thread, array $forum) {

    if (!empty($thread['threadmark_count'])) {
      $threadmarksModel = $this->_controller->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');

      if (!$threadmarksModel->canViewThreadmark($thread, $forum)) {
        return null;
      }

      $menuLimit = $threadmarksModel->getMenuLimit($thread);
      $threadmarks = $threadmarksModel->getRecentByThreadId($thread['thread_id'], $menuLimit);

      if (empty($threadmarks)) {
        return null;
      }

      $threadmarks = $threadmarksModel->prepareThreadmarks($threadmarks, $thread, $forum);
      $threadmarksParams = $this->_buildThreadmarksParams();
      $totalThreadmarks = $threadmarksParams['count'] = $thread['threadmark_count'];

      // $menuLimit 0 = unlimited
      if ($menuLimit > 0 && $totalThreadmarks > $menuLimit) {
        $threadmarksParams['more_threadmarks'] = true;
      }

      $threadmarksParams['recent'] = array_reverse($threadmarks);

      $threadmarksParams['threadmarks_post_ids'] = array_map(function($threadmark) {
        return $threadmark['post_id'];
      }, $threadmarks);

      return $threadmarksParams;
    }
  }

  protected function _buildThreadmarksParams() {
    return array(
      'hide_menu_from_guests' => XenForo_Application::get('options')->sidaneThreadmarksHideMenuFromGuests,
      'logged_in' => XenForo_Visitor::getUserId() != 0
    );
  }

}
