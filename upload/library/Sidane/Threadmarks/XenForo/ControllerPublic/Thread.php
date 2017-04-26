<?php

class Sidane_Threadmarks_XenForo_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_XenForo_ControllerPublic_Thread
{
  protected function _getPostFetchOptions(array $thread, array $forum)
  {
    $postFetchOptions = parent::_getPostFetchOptions($thread, $forum);

    if (
      empty($thread['threadmark_count']) ||
      (
        isset($postFetchOptions['includeThreadmark']) &&
        !$postFetchOptions['includeThreadmark']
      )
    )
    {
      return $postFetchOptions;
    }

    if ($this->_getThreadmarksModel()->canViewThreadmark($thread, $forum))
    {
      $postFetchOptions['includeThreadmark'] = true;
    }

    return $postFetchOptions;
  }

  public function actionIndex()
  {
    $response = parent::actionIndex();

    if (
      !$response instanceof XenForo_ControllerResponse_View ||
      empty($response->params['thread']) ||
      empty($response->params['forum'])
    )
    {
      return $response;
    }

    $viewParams = &$response->params;

    $thread = $viewParams['thread'];
    $forum = $viewParams['forum'];
    $canQuickReply = $viewParams['canQuickReply'];

    $threadmarksModel = $this->_getThreadmarksModel();

    $threadmarkCategories = $threadmarksModel->getAllThreadmarkCategories();
    $threadmarkCategories = $threadmarksModel->prepareThreadmarkCategories(
      $threadmarkCategories
    );
    $threadmarkCategoryPositions = $threadmarksModel
        ->getThreadmarkCategoryPositionsByThread($thread);

    if ($canQuickReply)
    {
      $canAddThreadmark = $threadmarksModel->canAddThreadmark(
        null,
        $thread,
        $forum,
        $null
      );
      $threadmarkCategoryOptions = array();

      if ($canAddThreadmark)
      {
        $threadmarkCategoryOptions = $threadmarksModel
          ->getThreadmarkCategoryOptions($threadmarkCategories, true);

        if (empty($threadmarkCategoryOptions))
        {
          $canAddThreadmark = false;
        }
      }

      $viewParams['canAddThreadmark'] = $canAddThreadmark;
      $viewParams['threadmarkCategoryOptions'] = $threadmarkCategoryOptions;
    }

    $viewParams['threadmarkCategories'] = $threadmarkCategories;
    $viewParams['threadmarkCategoryPositions'] = $threadmarkCategoryPositions;

    // draft support for threadmark fields
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
        $viewParams['draftThreadmarkLabel'] = $draftExtra['threadmark'];
      }

      if (!empty($draftExtra['threadmark_category_id']))
      {
        $viewParams['draftThreadmarkCategoryId'] = $draftExtra['threadmark_category_id'];
      }
    }

    $recentThreadmarks = $this->_getThreadmarksHelper()->getRecentThreadmarks(
      $thread,
      $forum
    );
    if (!empty($recentThreadmarks)) {
      $totalPosts = $viewParams['totalPosts'];
      $postsPerPage = $viewParams['postsPerPage'];

      $viewParams['singlePageThread'] = $totalPosts <= $postsPerPage;
      $viewParams['thread']['recentThreadmarks'] = $recentThreadmarks;
    }

    return $response;
  }

  protected function _assertCanReplyToThread(array $thread, array $forum)
  {
    parent::_assertCanReplyToThread($thread, $forum);

    if (
      Sidane_Threadmarks_Globals::$threadmarkLabel &&
      Sidane_Threadmarks_Globals::$threadmarkCategoryId
    )
    {
      if (!$this->_getThreadmarksModel()->canAddThreadmark(
        null,
        $thread,
        $forum,
        $errorPhraseKey
      ))
      {
        throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
      }
    }
  }

  public function actionSaveDraft()
  {
    Sidane_Threadmarks_Globals::$threadmarkLabel = $this->_input->filterSingle(
      'threadmark',
      XenForo_Input::STRING
    );
    Sidane_Threadmarks_Globals::$threadmarkCategoryId = $this->_input->filterSingle(
      'threadmark_category_id',
      XenForo_Input::UINT
    );

    return parent::actionSaveDraft();
  }

  public function actionAddReply()
  {
    Sidane_Threadmarks_Globals::$threadmarkLabel = $this->_input->filterSingle(
      'threadmark',
      XenForo_Input::STRING
    );
    Sidane_Threadmarks_Globals::$threadmarkCategoryId = $this->_input->filterSingle(
      'threadmark_category_id',
      XenForo_Input::UINT
    );

    return parent::actionAddReply();
  }

  protected function _getNewPosts(
    array $thread,
    array $forum,
    $lastDate,
    $limit = 3
  ) {
    if (
      Sidane_Threadmarks_Globals::$threadmarkLabel &&
      Sidane_Threadmarks_Globals::$threadmarkCategoryId &&
      empty($thread['threadmark_count'])
    )
    {
      $thread['threadmark_count'] = 1;
    }

    return parent::_getNewPosts($thread, $forum, $lastDate, $limit);
  }

  public function actionReply()
  {
    Sidane_Threadmarks_Globals::$threadmarkLabel = $this->_input->filterSingle(
      'threadmark',
      XenForo_Input::STRING
    );
    Sidane_Threadmarks_Globals::$threadmarkCategoryId = $this->_input->filterSingle(
      'threadmark_category_id',
      XenForo_Input::UINT
    );

    $response = parent::actionReply();

    if (
      !$response instanceof XenForo_ControllerResponse_View ||
      empty($response->params['thread']) ||
      empty($response->params['forum'])
    )
    {
      return $response;
    }

    $viewParams = &$response->params;

    $thread = $viewParams['thread'];
    $forum = $viewParams['forum'];

    $threadmarksModel = $this->_getThreadmarksModel();

    $canAddThreadmark = $threadmarksModel->canAddThreadmark(
      null,
      $thread,
      $forum,
      $null
    );
    $threadmarkCategoryOptions = array();

    if ($canAddThreadmark)
    {
      $threadmarkCategories = $threadmarksModel->getAllThreadmarkCategories();
      $threadmarkCategories = $threadmarksModel->prepareThreadmarkCategories(
        $threadmarkCategories
      );
      $threadmarkCategoryOptions = $threadmarksModel
        ->getThreadmarkCategoryOptions($threadmarkCategories, true);

      if (empty($threadmarkCategoryOptions))
      {
        $canAddThreadmark = false;
      }
    }

    $viewParams['canAddThreadmark'] = $canAddThreadmark;
    $viewParams['threadmarkCategoryOptions'] = $threadmarkCategoryOptions;

    // draft support for threadmark fields
    if ($this->_input->inRequest('more_options'))
    {
      $viewParams['draftThreadmarkLabel'] = Sidane_Threadmarks_Globals::$threadmarkLabel;
      $viewParams['draftThreadmarkCategoryId'] = Sidane_Threadmarks_Globals::$threadmarkCategoryId;
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
        $viewParams['draftThreadmarkLabel'] = $draftExtra['threadmark'];
      }

      if (!empty($draftExtra['threadmark_category_id']))
      {
        $viewParams['draftThreadmarkCategoryId'] = $draftExtra['threadmark_category_id'];
      }
    }

    return $response;
  }

  public function actionThreadmarksDisplayOrder()
  {
    // TODO
    $this->_assertPostOnly();

    $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
    $order = $this->_input->filterSingle('order', XenForo_Input::ARRAY_SIMPLE);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

    $threadmarksModel = $this->_getThreadmarksModel();

    if (
      empty($thread['threadmark_count']) ||
      !$threadmarksModel->canViewThreadmark($thread, $forum)
    ) {
      return $this->getNotFoundResponse();
    }

    $fetchOptions = $this->_getThreadmarkFetchOptions();

    $threadmarks = $threadmarksModel->getThreadmarksByThread(
      $thread['thread_id'],
      $fetchOptions
    );
    $threadmarks = $threadmarksModel->prepareThreadmarks(
      $threadmarks,
      $thread,
      $forum
    );

    foreach ($threadmarks as &$threadmark)
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

    $threadFetchOptions = array('readUserId' => $visitor['user_id']);
    $forumFetchOptions = array('readUserId' => $visitor['user_id']);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable(
      $threadId,
      $threadFetchOptions,
      $forumFetchOptions
    );

    $threadmarksModel = $this->_getThreadmarksModel();

    $threadmarkCategoryPositions = $threadmarksModel
      ->getThreadmarkCategoryPositionsByThread($thread);

    if (empty($threadmarkCategoryPositions))
    {
      return $this->getNotFoundResponse();
    }

    $threadmarkCategories = $threadmarksModel->getAllThreadmarkCategories();
    foreach ($threadmarkCategories as $threadmarkCategoryId => $threadmarkCategory)
    {
      if (empty($threadmarkCategoryPositions[$threadmarkCategoryId]))
      {
        unset($threadmarkCategories[$threadmarkCategoryId]);
      }
    }

    if (empty($threadmarkCategories))
    {
      return $this->getNotFoundResponse();
    }

    $threadmarkCategoryId = $this->_input->filterSingle(
      'category_id',
      XenForo_Input::STRING
    );
    if (!$threadmarkCategoryId)
    {
      $threadmarkCategoryId = reset($threadmarkCategories)['threadmark_category_id'];
    }

    if (empty($threadmarkCategories[$threadmarkCategoryId]))
    {
      return $this->getNotFoundResponse();
    }

    if (!$threadmarksModel->canViewThreadmark($thread, $forum))
    {
      return $this->responseNoPermission();
    }

    $threadmarkCategories = $threadmarksModel->prepareThreadmarkCategories(
      $threadmarkCategories
    );
    $activeThreadmarkCategory = $threadmarkCategories[$threadmarkCategoryId];

    $threadmarks = $threadmarksModel->getThreadmarksByThreadAndCategory(
      $thread['thread_id'],
      $threadmarkCategoryId,
      $this->_getThreadmarkFetchOptions()
    );
    $threadmarks = $threadmarksModel->prepareThreadmarks(
      $threadmarks,
      $thread,
      $forum
    );

    $canEditThreadMarks = false;
    foreach($threadmarks as $threadmark)
    {
      if (!empty($threadmark['canEdit']))
      {
        $canEditThreadMarks = true;
        break;
      }
    }

    $viewParams = array(
      'threadmarkCategories'     => $threadmarkCategories,
      'threadmarkCategoryCount'  => count($threadmarkCategories),
      'activeThreadmarkCategory' => $activeThreadmarkCategory,
      'threadmarks'              => $threadmarks,
      'canEditThreadMarks'       => $canEditThreadMarks,
      'forum'                    => $forum,
      'thread'                   => $thread
    );

    return $this->responseView(
      'Sidane_Threadmarks_ViewPublic_Thread_View',
      'threadmarks',
      $viewParams
    );
  }

  protected function _getThreadmarkFetchOptions()
  {
    return array(
      'join' => Sidane_Threadmarks_Model_Threadmarks::FETCH_POSTS_MINIMAL
    );
  }

  protected function _getThreadmarksHelper()
  {
    return $this->getHelper('Sidane_Threadmarks_ControllerHelper_Threadmarks');
  }

  /**
   * For backwards-compatibility.
   */
  protected function _threadmarksHelper()
  {
    return $this->_getThreadmarksHelper();
  }

  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
