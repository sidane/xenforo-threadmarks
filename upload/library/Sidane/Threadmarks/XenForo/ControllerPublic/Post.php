<?php

class Sidane_Threadmarks_XenForo_ControllerPublic_Post extends XFCP_Sidane_Threadmarks_XenForo_ControllerPublic_Post
{
  public function actionThreadmarkPreview()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
    $ftpHelper = $this->getHelper('ForumThreadPost');

    try
    {
      list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(
        $postId,
        array('join' => XenForo_Model_Post::FETCH_USER)
      );
    }
    catch(Exception $e) {
      return $this->responseView(
        'XenForo_ViewPublic_Thread_Preview',
        '',
        array('post' => false)
      );
    }

    $threadmarksModel = $this->_getThreadmarksModel();
    $threadmark = $threadmarksModel->getByPostId($post['post_id']);

    if (empty($threadmark)) {
      return $this->responseView(
        'XenForo_ViewPublic_Thread_Preview',
        '',
        array('post' => false)
      );
    }

    $viewParams = array(
      'threadmark' => $threadmark,
      'post'       => $post,
      'thread'     => $thread,
      'forum'      => $forum
    );

    return $this->responseView(
      'XenForo_ViewPublic_Thread_Preview',
      'threadmark_preview',
      $viewParams
    );
  }

  public function actionThreadmark()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(
      $postId
    );

    $threadmarksModel = $this->_getThreadmarksModel();
    $existingThreadmark = $threadmarksModel->getByPostId($post['post_id']);

    if ($this->_request->isPost())
    {
      $input = $this->_input->filter(array(
        'label'                  => XenForo_Input::STRING,
        'threadmark_category_id' => XenForo_Input::UINT
      ));

      if ($input['label'] === '')
      {
        return $this->responseError(new XenForo_Phrase(
          'please_enter_label_for_threadmark'
        ));
      }

      if ($existingThreadmark)
      {
        if ($this->isConfirmedPost())
        {
          if (!$threadmarksModel->canDeleteThreadmark($post, $thread, $forum))
          {
            throw $this->getErrorOrNoPermissionResponseException(
              'you_do_not_have_permission_to_delete_threadmarks'
            );
          }

          $threadmarksModel->deleteThreadmark($existingThreadmark);

          $phrase = 'threadmark_deleted';

          XenForo_Model_Log::logModeratorAction(
            'post',
            $post,
            'delete_threadmark',
            array(
              'label'                  => $existingThreadmark['label'],
              'threadmark_category_id' => $existingThreadmark['threadmark_category_id']
            ),
            $thread
          );
        }
        else
        {
          if (!$threadmarksModel->canEditThreadmark(
            $existingThreadmark,
            $post,
            $thread,
            $forum
          ))
          {
            throw $this->getErrorOrNoPermissionResponseException(
              'you_do_not_have_permission_to_edit_threadmarks'
            );
          }

          $threadmarksModel->setThreadMark(
            $thread,
            $post,
            $input['label'],
            $input['threadmark_category_id']
          );

          $phrase = 'threadmark_updated';

          XenForo_Model_Log::logModeratorAction(
            'post',
            $post,
            'update_threadmark',
            array(
              'old_label'                  => $existingThreadmark['label'],
              'new_label'                  => $input['label'],
              'old_threadmark_category_id' => $existingThreadmark['threadmark_category_id'],
              'new_threadmark_category_id' => $input['threadmark_category_id']
            ),
            $thread
          );
        }
      }
      else
      {
        if (!$threadmarksModel->canAddThreadmark($post, $thread, $forum))
        {
          throw $this->getErrorOrNoPermissionResponseException(
            'you_do_not_have_permission_to_add_threadmarks'
          );
        }
        $resetNesting = $this->_input->filterSingle(
            'reset_nesting',
            XenForo_Input::BOOLEAN
        );
        $position = $this->_input->filterSingle(
            'position',
            XenForo_Input::UINT
        );
        $threadmarksModel->setThreadMark(
          $thread,
          $post,
          $input['label'],
          $input['threadmark_category_id'],
          $position,
          $resetNesting
        );
        $phrase = 'threadmark_created';
        XenForo_Model_Log::logModeratorAction(
          'post',
          $post,
          'create_threadmark',
          array(
            'label'                  => $input['label'],
            'threadmark_category_id' => $input['threadmark_category_id']
          ),
          $thread
        );
      }

      $controllerResponse = $this->getPostSpecificRedirect($post, $thread);
      $controllerResponse->redirectMessage = new XenForo_Phrase($phrase);

      return $controllerResponse;
    }
    else
    {
      $threadmarkCategories = $threadmarksModel->getThreadmarkCategoryOptions(
        true
      );

      $viewParams = array(
        'post'                 => $post,
        'thread'               => $thread,
        'forum'                => $forum,
        'threadmarkCategories' => $threadmarkCategories,
        'nodeBreadCrumbs'      => $ftpHelper->getNodeBreadCrumbs($forum),
      );

      if ($existingThreadmark)
      {
        $canEditThreadmark = $threadmarksModel->canEditThreadmark(
          $existingThreadmark,
          $post,
          $thread,
          $forum
        );
        $canDeleteThreadmark = $threadmarksModel->canDeleteThreadmark(
          $existingThreadmark,
          $post,
          $thread,
          $forum
        );

        if (!$canEditThreadmark && !$canDeleteThreadmark)
        {
          throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_edit_threadmarks');
        }
        $viewParams['threadmark'] = $existingThreadmark;
        $templateName = 'edit_threadmark';
      }
      else
      {
        $category = $threadmarksModel->getDefaultThreadmarkCategory();
        if (empty($category) || !$threadmarksModel->canAddThreadmark($post, $thread, $forum))
        {
          throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_add_threadmarks');
        }
        $templateName = 'new_threadmark';

        $viewParams['threadmark_category_id'] = $category['threadmark_category_id'];
        $viewParams['previousThreadmark'] = $threadmarksModel->getPreviousThreadmarkByPost($category['threadmark_category_id'], $post['thread_id'], $post['position']);
        $viewParams['lastThreadmark'] = $threadmarksModel->getPreviousThreadmarkByLocation($category['threadmark_category_id'], $post['thread_id']);
      }

      return $this->responseView(
        'Sidane_Threadmarks_ViewPublic_Post_Threadmark',
        $templateName,
        $viewParams
      );
    }
  }

  public function actionAddThreadmarkPosition()
  {
    $postId = $this->_input->filterSingle(
        'post_id',
        XenForo_Input::UINT
    );
    $categoryId = $this->_input->filterSingle(
        'position',
        XenForo_Input::UINT
    );

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(
      $postId
    );

    $threadmarksModel = $this->_getThreadmarksModel();
    $category = $threadmarksModel->getThreadmarkCategoryById($categoryId);
    if (empty($category) || !$threadmarksModel->canAddThreadmark($post, $thread, $forum))
    {
      throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_add_threadmarks');
    }

    $view = $this->responseView();
    $view->jsonParams = array(
        'previousThreadmark' => $threadmarksModel->getPreviousThreadmarkByPost($category['threadmark_category_id'], $post['thread_id'], $post['position']),
        'lastThreadmark' => $threadmarksModel->getPreviousThreadmarkByLocation($category['threadmark_category_id'], $post['thread_id']),
    );
    return $view;
  }

  public function actionNextThreadmark()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(
      $postId
    );

    $threadmarksModel = $this->_getThreadmarksModel();
    $threadmark = $threadmarksModel->getByPostId($post['post_id']);
    if ($threadmark)
    {
      $nextThreadmark = $threadmarksModel->getNextThreadmark($threadmark);
      list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(
        @$nextThreadmark['post_id']
      );
    }

    return $this->getPostSpecificRedirect($post, $thread);
  }

  public function actionPreviousThreadmark()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(
      $postId
    );

    $threadmarksModel = $this->_getThreadmarksModel();
    $threadmark = $threadmarksModel->getByPostId($post['post_id']);
    if ($threadmark)
    {
      $previousThreadmark = $threadmarksModel->getPreviousThreadmark($threadmark);
      list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(
        @$previousThreadmark['post_id']
      );
    }

    return $this->getPostSpecificRedirect($post, $thread);
  }

  public function actionThreadmarkHistory()
  {
    $this->_request->setParam('content_type', 'threadmark');
    $this->_request->setParam(
      'content_id',
      $this->_input->filterSingle('post_id', XenForo_Input::UINT)
    );

    return $this->responseReroute(
      'XenForo_ControllerPublic_EditHistory',
      'index'
    );
  }

  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
