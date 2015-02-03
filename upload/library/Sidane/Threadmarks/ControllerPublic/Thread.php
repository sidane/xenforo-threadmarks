<?php

class Sidane_Threadmarks_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_ControllerPublic_Thread
{
  protected function _getPostFetchOptions(array $thread, array $forum)
  {
    $postFetchOptions = parent::_getPostFetchOptions($thread, $forum);
    $threadmarkmodel = $this->_getThreadmarksModel();

    if ($threadmarkmodel->canViewThreadmark($thread))
    {
      $postFetchOptions['join'] |= Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS;
    }

    return $postFetchOptions;
  }

  public function actionIndex()
  {
    $parent = parent::actionIndex();

    if (get_class($parent) == 'XenForo_ControllerResponse_Redirect') {
      return $parent;
    }

    $threadmarksHelper = $this->_threadmarksHelper();
    $recentThreadmarks = $threadmarksHelper->getRecentThreadmarks($parent->params['thread']);
    if (!empty($recentThreadmarks)) {
      $parent->params['singlePageThread'] = $parent->params['totalPosts'] <= $parent->params['postsPerPage'];
      $parent->params['thread']['recentThreadmarks'] = $recentThreadmarks;
    }

    return $parent;
  }

  public function actionThreadmarks()
  {
    $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);
    $threadmarksModel = $this->_getThreadmarksModel();
    if (!empty($thread['threadmark_count']) && $threadmarksModel->canViewThreadmark($thread)) {
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

  private function _getThreadmarksModel() {
    return $this->getHelper('Sidane_Threadmarks_Model_Threadmarks');
  }
}
