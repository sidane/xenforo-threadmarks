<?php

class Sidane_Threadmarks_XenForo_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_XenForo_ControllerPublic_Thread
{
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

    $threadmarksModel = $this->_getThreadmarksModel();
    if (!$threadmarksModel->canViewThreadmark($thread, $forum))
    {
      return $response;
    }

    $viewParams = &$response->params;

    $thread = $viewParams['thread'];
    $forum = $viewParams['forum'];
    $canQuickReply = $viewParams['canQuickReply'];
    $threadmarkCategories = $viewParams['threadmarkCategories'];

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
    if (!empty($recentThreadmarks))
    {
      $totalPosts = $viewParams['totalPosts'];
      $postsPerPage = $viewParams['postsPerPage'];

      $viewParams['singlePageThread'] = $totalPosts <= $postsPerPage;
      $viewParams['thread']['recentThreadmarks'] = $recentThreadmarks;
    }

    return $response;
  }

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
      return $this->responseError(
        new XenForo_Phrase('requested_page_not_found'),
        404
      );
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
      return $this->responseError(
        new XenForo_Phrase('requested_page_not_found'),
        404
      );
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
      return $this->responseError(
        new XenForo_Phrase('requested_page_not_found'),
        404
      );
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
      $activeThreadmarkCategory['threadmark_category_id'],
      $this->_getThreadmarkFetchOptions($thread, $forum)
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
      'activeThreadmarkCategory' => $activeThreadmarkCategory,

      'threadmarks'        => $threadmarks,
      'canEditThreadMarks' => $canEditThreadMarks,

      'forum'  => $forum,
      'thread' => $thread,

      'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum)
    );

    return $this->responseView(
      'XenForo_ViewPublic_Thread_Threadmarks',
      'threadmarks',
      $viewParams
    );
  }

  public function actionThreadmarksLoadTree()
  {
    $this->_assertPostOnly();

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

    $threadmarkCategoryId = $this->_input->filterSingle(
      'category_id',
      XenForo_Input::STRING
    );

    if (empty($threadmarkCategoryPositions[$threadmarkCategoryId]))
    {
      return $this->responseError(
        new XenForo_Phrase('requested_page_not_found'),
        404
      );
    }

    $threadmarkCategory = $threadmarksModel->getThreadmarkCategoryById(
      $threadmarkCategoryId
    );

    if (empty($threadmarkCategory))
    {
      return $this->responseError(
        new XenForo_Phrase('requested_page_not_found'),
        404
      );
    }

    $threadmarks = $threadmarksModel->getThreadmarksByThreadAndCategory(
      $thread['thread_id'],
      $threadmarkCategory['threadmark_category_id'],
      $this->_getThreadmarkFetchOptions($thread, $forum)
    );

    $canEditThreadMarks = false;
    foreach ($threadmarks as $threadmark)
    {
      if ($threadmarksModel->canEditThreadmark(
        $threadmark,
        $threadmark,
        $thread,
        $forum
      ))
      {
        $canEditThreadMarks = true;
        break;
      }
    }

    if (!$canEditThreadMarks)
    {
      throw $this->getErrorOrNoPermissionResponseException(
        'you_do_not_have_permission_to_edit_threadmark'
      );
    }

    $treeItems = array();
    foreach ($threadmarks as $threadmarkId => $threadmark)
    {
      $treeItem = array();

      $parent = $threadmark['parent_threadmark_id'];
      if ($parent === 0)
      {
        $parent = '#';
      }

      $treeItem['id'] = $threadmark['threadmark_id'];
      $treeItem['text'] = $threadmark['label'];
      $treeItem['parent'] = $parent;

      $treeItems[] = $treeItem;
    }

    $this->_routeMatch->setResponseType('json');

    $viewParams = array(
      'tree' => $treeItems
    );

    return $this->responseView(
      'XenForo_ViewPublic_Thread_ThreadmarksLoadTree',
      '',
      $viewParams
    );
  }

  public function actionThreadmarksSyncTree()
  {
    $this->_assertPostOnly();

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

    $threadmarkCategoryId = $this->_input->filterSingle(
      'category_id',
      XenForo_Input::STRING
    );

    if (empty($threadmarkCategoryPositions[$threadmarkCategoryId]))
    {
      return $this->responseError(
        new XenForo_Phrase('requested_page_not_found'),
        404
      );
    }

    $threadmarkCategory = $threadmarksModel->getThreadmarkCategoryById(
      $threadmarkCategoryId
    );

    if (empty($threadmarkCategory))
    {
      return $this->responseError(
        new XenForo_Phrase('requested_page_not_found'),
        404
      );
    }

    $threadmarks = $threadmarksModel->getThreadmarksByThreadAndCategory(
      $thread['thread_id'],
      $threadmarkCategory['threadmark_category_id'],
      $this->_getThreadmarkFetchOptions($thread, $forum)
    );

    $canEditThreadMarks = false;
    foreach ($threadmarks as $threadmark)
    {
      if ($threadmarksModel->canEditThreadmark(
        $threadmark,
        $threadmark,
        $thread,
        $forum
      ))
      {
        $canEditThreadMarks = true;
        break;
      }
    }

    if (!$canEditThreadMarks)
    {
      throw $this->getErrorOrNoPermissionResponseException(
        'you_do_not_have_permission_to_edit_threadmark'
      );
    }

    $treeItems = $this->_input->filterSingle(
      'tree',
      XenForo_Input::JSON_ARRAY
    );

    $threadmarks = array();
    foreach ($treeItems as $treeItem)
    {
      $threadmark = array();

      $threadmarkId = $treeItem['id'];
      $parentThreadmarkId = $treeItem['parent'];
      if ($parentThreadmarkId == '#')
      {
        $parentThreadmarkId = 0;
      }

      $threadmark['threadmark_id'] = $threadmarkId;
      $threadmark['parent_threadmark_id'] = $parentThreadmarkId;

      $threadmarks[$threadmarkId] = $threadmark;
    }

    $tree = $threadmarksModel->buildThreadmarkTree($threadmarks);
    $threadmarks = $threadmarksModel->flattenThreadmarkTree($tree);
    $threadmarksModel->massUpdateDisplayOrder(
      $thread['thread_id'],
      $threadmarkCategory['threadmark_category_id'],
      $threadmarks
    );

    return $this->responseRedirect(
      XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
      XenForo_Link::buildPublicLink('threads/threadmarks', $thread)
    );
  }

  protected function _getDefaultViewParams(
    array $forum,
    array $thread,
    array $posts,
    $page = 1,
    array $viewParams = array()
  ) {
    $defaultViewParams = parent::_getDefaultViewParams(
      $forum,
      $thread,
      $posts,
      $page,
      $viewParams
    );

    $threadmarksModel = $this->_getThreadmarksModel();

    if (
        !$threadmarksModel->canViewThreadmark($thread, $forum) ||
        (isset($viewParams['firstPost']) && empty($thread['canQuickReply']) && empty($thread['threadmark_count'])
    )
    {
      return $defaultViewParams;
    }

    $threadmarkCategories = $threadmarksModel->getAllThreadmarkCategories();
    $threadmarkCategories = $threadmarksModel->prepareThreadmarkCategories(
      $threadmarkCategories
    );

    $defaultViewParams['threadmarkCategories'] = $threadmarkCategories;

    return $defaultViewParams;
  }

  protected function _getThreadmarkFetchOptions(array $thread = null, array $forum = null)
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

  /**
   * @return Sidane_Threadmarks_Model_Threadmarks
   */
  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
