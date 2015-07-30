<?php

class Sidane_Threadmarks_ControllerPublic_Post extends XFCP_Sidane_Threadmarks_ControllerPublic_Post
{
  public function actionThreadmarkPreview()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
    $ftpHelper = $this->getHelper('ForumThreadPost');
    
    try
    {
      list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);
    }
    catch(Exception $e) {
      return $this->responseView('XenForo_ViewPublic_Thread_Preview', '', array('post' => false));
    }
    
    $threadmarksModel = $this->_getThreadmarksModel();
    $threadmark = $threadmarksModel->getByPostId($post['post_id']);
    
    if (empty($threadmark)) {
      return $this->responseView('XenForo_ViewPublic_Thread_Preview', '', array('post' => false));
    }
    
    $viewParams = array(
      'threadmark' => $threadmark,
      'post' => $post,
      'thread' => $thread,
      'forum' => $forum
    );

    return $this->responseView('XenForo_ViewPublic_Thread_Preview', 'threadmark_preview', $viewParams);
  }

  public function actionThreadmark()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

    $threadmarksModel = $this->_getThreadmarksModel();
    $existingThreadmark = $threadmarksModel->getByPostId( $post['post_id']);

    if ($this->_request->isPost())
    {
      $label = $this->_input->filterSingle('label', XenForo_Input::STRING);
      if (!$label)
      {
        return $this->responseError(new XenForo_Phrase('please_enter_label_for_threadmark'));
      }

      if ($existingThreadmark) {
        if ($this->isConfirmedPost()) {
          if (!$threadmarksModel->canDeleteThreadmark($post, $thread, $forum)) {
            throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_delete_threadmarks');
          }
          $threadmarksModel->deleteThreadmark($existingThreadmark);
          $phrase = 'threadmark_deleted';
          XenForo_Model_Log::logModeratorAction(
            'post', $post, 'delete_threadmark', array('label' => $existingThreadmark['label']), $thread
          );
        } else {
          if (!$threadmarksModel->canEditThreadmark($post, $thread, $forum)) {
            throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_edit_threadmarks');
          }
          $threadmarksModel->setThreadMark($thread['thread_id'], $post['post_id'], $label);
          $phrase = 'threadmark_updated';
          XenForo_Model_Log::logModeratorAction(
            'post', $post, 'update_threadmark', array('old_label' => $existingThreadmark['label'], 'new_label' => $label), $thread
          );
        }
      } else {
        if (!$threadmarksModel->canAddThreadmark($post, $thread, $forum)) {
          throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_add_threadmarks');
        }
        $threadmarksModel->setThreadMark($thread['thread_id'], $post['post_id'], $label);
        $phrase = 'threadmark_created';
        XenForo_Model_Log::logModeratorAction(
          'post', $post, 'create_threadmark', array('label' => $label), $thread
        );
      }

      $controllerResponse = $this->getPostSpecificRedirect($post, $thread);
      $controllerResponse->redirectMessage = new XenForo_Phrase($phrase);
      return $controllerResponse;
    }
    else
    {
      $viewParams = array(
        'post' => $post,
        'thread' => $thread,
        'forum' => $forum,
        'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
      );

      if ($existingThreadmark) {
        if (!$threadmarksModel->canEditThreadmark($post, $thread, $forum) && !$threadmarksModel->canDeleteThreadmark($post, $thread, $forum)) {
          throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_edit_threadmarks');
        }
        $viewParams['threadmark'] = $existingThreadmark;
        $templateName = 'edit_threadmark';
      } else {
        if (!$threadmarksModel->canAddThreadmark($post, $thread, $forum)) {
          throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_add_threadmarks');
        }
        $templateName = 'new_threadmark';
      }

      return $this->responseView('Sidane_Threadmarks_ViewPublic_Post_Threadmark', $templateName, $viewParams);
    }
  }

  public function actionNextThreadmark()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

    $threadmarksModel = $this->_getThreadmarksModel();
    $threadmark = $threadmarksModel->getByPostId($post['post_id']);
    if ($threadmark)
    {
      $threadmark = $threadmarksModel->getNextThreadmark($threadmark);
      list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(@$threadmark['post_id']);
    }

    return $this->getPostSpecificRedirect($post, $thread);
  }

  public function actionPreviousThreadmark()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);
    
    $threadmarksModel = $this->_getThreadmarksModel();
    $threadmark = $threadmarksModel->getByPostId($post['post_id']);
    if ($threadmark)
    {
      $threadmark = $threadmarksModel->getPreviousThreadmark($threadmark);
      list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable(@$threadmark['post_id']);
    }

    return $this->getPostSpecificRedirect($post, $thread);
  }

  public function actionThreadmarkHistory()
  {
    $this->_request->setParam('content_type', 'threadmark');
    $this->_request->setParam('content_id', $this->_input->filterSingle('post_id', XenForo_Input::UINT));
    return $this->responseReroute('XenForo_ControllerPublic_EditHistory', 'index');
  }

  protected function _getThreadmarksModel() {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}