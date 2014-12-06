<?php

class Sidane_Threadmarks_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_ControllerPublic_Thread
{

  public function actionIndex()
  {
    $parent = parent::actionIndex();

    if (get_class($parent) == 'XenForo_ControllerResponse_Redirect') {
      return $parent;
    }

    $thread = $parent->params['thread'];

    if ($thread['has_threadmarks']) {
      $threadmarksModel = $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
      $menuLimit = intval(XenForo_Application::get('options')->threadmarksLimit);

      $threadmarksParams = array();

      $threadmarks = $threadmarksModel->getByThreadId($thread['thread_id']);
      $totalThreadmarks = count($threadmarks);

      if ($totalThreadmarks == 0) {
        return $parent;
      }

      // to allow for changing the template modification
      // based on whether user is logged in or not
      $threadmarksParams['logged_in'] = XenForo_Visitor::getUserId() != 0;

      if ($totalThreadmarks > $menuLimit) {
        $threadmarks = array_slice($threadmarks, $totalThreadmarks - $menuLimit, null, true);
        $threadmarksParams['more_threadmarks'] = true;
      }

      $threadmarksParams['all'] = $threadmarks;
      $threadmarksParams['count'] = $totalThreadmarks;

      $threadmarksParams['threadmarks_post_ids'] = array_map(function($threadmark) {
        return $threadmark['post_id'];
      }, $threadmarks);

      $parent->params['thread']['threadmarks'] = $threadmarksParams;
    }

    return $parent;
  }

  public function actionThreadmarks()
  {
    $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

    if ($thread['has_threadmarks']) {
      $threadmarksModel = $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
      $threadmarks = $threadmarksModel->getByThreadIdWithPostDate($thread['thread_id']);

      $viewParams = array(
        'forum' => $forum,
        'thread' => $thread,
        'threadmarks' => $threadmarks
      );

      return $this->responseView('Sidane_Threadmarks_ViewPublic_Thread_View', 'threadmarks', $viewParams);
    } else {
      return $this->getNotFoundResponse();
    }
  }

}
