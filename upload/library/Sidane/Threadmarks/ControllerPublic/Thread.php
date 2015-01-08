<?php

class Sidane_Threadmarks_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_ControllerPublic_Thread
{

  public function actionIndex()
  {
    $parent = parent::actionIndex();

    if (get_class($parent) == 'XenForo_ControllerResponse_Redirect') {
      return $parent;
    }

    $threadmarksHelper = $this->_threadmarksHelper();
    $threadmarks = $threadmarksHelper->getThreadmarks($parent->params['thread']);
    if (!empty($threadmarks)) {
      $parent->params['thread']['threadmarks'] = $threadmarks;
    }

    return $parent;
  }

  public function actionThreadmarks()
  {
    $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

    if (!empty($thread['threadmark_count'])) {
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

  private function _threadmarksHelper() {
    return $this->getHelper('Sidane_Threadmarks_ControllerHelper_Threadmarks');
  }

}
