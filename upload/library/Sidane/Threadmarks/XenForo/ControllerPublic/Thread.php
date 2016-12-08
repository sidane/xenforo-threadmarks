<?php

class Sidane_Threadmarks_XenForo_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_XenForo_ControllerPublic_Thread
{
  protected function _getPostFetchOptions(array $thread, array $forum)
  {
    $postFetchOptions = parent::_getPostFetchOptions($thread, $forum);
    if (empty($thread['threadmark_count']) || isset($postFetchOptions['includeThreadmark']) && !$postFetchOptions['includeThreadmark'])
    {
      return $postFetchOptions;
    }

    $threadmarkmodel = $this->_getThreadmarksModel();
    if ($threadmarkmodel->canViewThreadmark($thread, $forum))
    {
      $postFetchOptions['includeThreadmark'] = true;
    }

    return $postFetchOptions;
  }

  public function actionIndex()
  {
    $parent = parent::actionIndex();

    if ($parent instanceof XenForo_ControllerResponse_View &&
        !empty($parent->params['thread']) && !empty($parent->params['forum']))
    {
        $thread = $parent->params['thread'];
        $threadmarkmodel = $this->_getThreadmarksModel();
        if ($parent->params['canQuickReply'])
        {
          $parent->params['canAddThreadmark'] = $threadmarkmodel->canAddThreadmark(null, $thread, $parent->params['forum'], $null);
        }
        // draft support for the threadmark field
        if (!empty($thread['draft_extra']))
        {
          if (is_callable('XenForo_Helper_Php::safeUnserialize'))
          {
            $draftExtra = XenForo_Helper_Php::safeUnserialize($thread['draft_extra']);
          }
          else
          {
            $draftExtra = @unserialize($thread['draft_extra']);
          }
          if (!empty($draftExtra['threadmark']))
          {
            $parent->params['threadmarkLabel'] = $draftExtra['threadmark'];
          }
        }

        $threadmarksHelper = $this->_threadmarksHelper();
        $recentThreadmarks = $threadmarksHelper->getRecentThreadmarks($thread, $parent->params['forum']);
        if (!empty($recentThreadmarks)) {
          $parent->params['singlePageThread'] = $parent->params['totalPosts'] <= $parent->params['postsPerPage'];
          $parent->params['thread']['recentThreadmarks'] = $recentThreadmarks;
        }
    }

    return $parent;
  }

  protected function _assertCanReplyToThread(array $thread, array $forum)
  {
    parent::_assertCanReplyToThread($thread, $forum);
    if (Sidane_Threadmarks_Globals::$threadmarkLabel)
    {
      if (!$this->_getThreadmarksModel()->canAddThreadmark(null, $thread, $forum, $errorPhraseKey))
      {
        throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
      }
    }
  }

  public function actionSaveDraft()
  {
    Sidane_Threadmarks_Globals::$threadmarkLabel = $this->_input->filterSingle('threadmark', XenForo_Input::STRING);

    return parent::actionSaveDraft();
  }

  public function actionAddReply()
  {
    Sidane_Threadmarks_Globals::$threadmarkLabel = $this->_input->filterSingle('threadmark', XenForo_Input::STRING);

    return parent::actionAddReply();
  }

  protected function _getNewPosts(array $thread, array $forum, $lastDate, $limit = 3)
  {
      if (Sidane_Threadmarks_Globals::$threadmarkLabel && empty($thread['threadmark_count']))
      {
          $thread['threadmark_count'] = 1;
      }
      return parent::_getNewPosts($thread, $forum, $lastDate, $limit);
  }

  public function actionReply()
  {
    Sidane_Threadmarks_Globals::$threadmarkLabel = $this->_input->filterSingle('threadmark', XenForo_Input::STRING);

    $parent = parent::actionReply();

    if ($parent instanceof XenForo_ControllerResponse_View &&
        !empty($parent->params['thread']) && !empty($parent->params['forum']))
    {
        $thread = $parent->params['thread'];
        $threadmarkmodel = $this->_getThreadmarksModel();
        $parent->params['canAddThreadmark'] = $threadmarkmodel->canAddThreadmark(null, $thread, $parent->params['forum'], $null);

        // draft support for the threadmark field
        if ($this->_input->inRequest('more_options'))
        {
           $parent->params['threadmarkLabel'] = Sidane_Threadmarks_Globals::$threadmarkLabel;
        }
        else if (!empty($thread['draft_extra']))
        {
          if (is_callable('XenForo_Helper_Php::safeUnserialize'))
          {
            $draftExtra = XenForo_Helper_Php::safeUnserialize($thread['draft_extra']);
          }
          else
          {
            $draftExtra = @unserialize($thread['draft_extra']);
          }
          if (!empty($draftExtra['threadmark']))
          {
            $parent->params['threadmarkLabel'] = $draftExtra['threadmark'];
          }
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
