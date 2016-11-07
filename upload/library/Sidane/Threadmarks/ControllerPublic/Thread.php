<?php

class Sidane_Threadmarks_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_ControllerPublic_Thread
{
  protected function _getPostFetchOptions(array $thread, array $forum)
  {
    $postFetchOptions = parent::_getPostFetchOptions($thread, $forum);
    $threadmarkmodel = $this->_getThreadmarksModel();

    if ($threadmarkmodel->canViewThreadmark($thread, $forum))
    {
      $postFetchOptions['join'] |= Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS;
    }

    return $postFetchOptions;
  }

  public function actionIndex()
  {
    $parent = parent::actionIndex();

    if ($parent instanceof XenForo_ControllerResponse_View &&
        !empty($parent->params['thread']) && !empty($parent->params['forum']))
    {
        $threadmarksHelper = $this->_threadmarksHelper();
        $recentThreadmarks = $threadmarksHelper->getRecentThreadmarks($parent->params['thread'], $parent->params['forum']);
        if (!empty($recentThreadmarks)) {
          $parent->params['singlePageThread'] = $parent->params['totalPosts'] <= $parent->params['postsPerPage'];
          $parent->params['thread']['recentThreadmarks'] = $recentThreadmarks;
        }
    }

    return $parent;
  }

  public function actionThreadmarksDisplayOrder()
  {
    $this->_assertPostOnly();

    $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
    $order = $this->_input->filterSingle('order', XenForo_Input::ARRAY_SIMPLE);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

    $threadmarksModel = $this->_getThreadmarksModel();
    if (empty($thread['threadmark_count']) || !$threadmarksModel->canViewThreadmark($thread, $forum)) {
      return $this->getNotFoundResponse();
    }

    $fetchOptions = $this->_getThreadmarkFetchOptions();

    $threadmarks = $threadmarksModel->getThreadmarksByThread($thread['thread_id'], $fetchOptions);
    $threadmarks = $threadmarksModel->prepareThreadmarks($threadmarks, $thread, $forum);

    foreach($threadmarks as &$threadmark)
    {
        if (empty($threadmark['canEdit']))
        {
            return $this->getNoPermissionResponseException();
        }
    }

    $threadmarksModel->massUpdateDisplayOrder($thread['thread_id'], $order);

    return $this->responseRedirect(
      XenForo_ControllerResponse_Redirect::SUCCESS,
      XenForo_Link::buildPublicLink('threads/threadmarks', $thread)
    );
  }

  public function actionThreadmarks()
  {
    $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

    $visitor = XenForo_Visitor::getInstance();
    $threadFetchOptions = array(
      'readUserId' => $visitor['user_id'],
    );
    $forumFetchOptions = array(
      'readUserId' => $visitor['user_id'],
    );
    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);
    $threadmarksModel = $this->_getThreadmarksModel();
    if (!empty($thread['threadmark_count']) && $threadmarksModel->canViewThreadmark($thread, $forum)) {

      $fetchOptions = $this->_getThreadmarkFetchOptions();

      $threadmarks = $threadmarksModel->getThreadmarksByThread($thread['thread_id'], $fetchOptions);

      $threadmarks = $threadmarksModel->prepareThreadmarks($threadmarks, $thread, $forum);
      $threadmarks = $threadmarksModel->preparelistToTree($threadmarks);

      $canEditThreadMarks = false;
      foreach($threadmarks as &$threadmark)
      {
        if (!empty($threadmark['canEdit']))
        {
          $canEditThreadMarks = true;
          break;
        }
      }

      $viewParams = array(
        'forum' => $forum,
        'thread' => $thread,
        'threadmarks' => $threadmarks,
        'canEditThreadMarks' => $canEditThreadMarks,
      );

      return $this->responseView('Sidane_Threadmarks_ViewPublic_Thread_View', 'threadmarks', $viewParams);
    } else {
      return $this->getNotFoundResponse();
    }
  }

  protected function _getThreadmarkFetchOptions()
  {
    return array(
      'join' => Sidane_Threadmarks_Model_Threadmarks::FETCH_POSTS_MINIMAL
    );
  }

  private function _threadmarksHelper() {
    return $this->getHelper('Sidane_Threadmarks_ControllerHelper_Threadmarks');
  }

  private function _getThreadmarksModel() {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
