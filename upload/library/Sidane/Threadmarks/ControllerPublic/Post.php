<?php

class Sidane_Threadmarks_ControllerPublic_Post extends XFCP_Sidane_Threadmarks_ControllerPublic_Post
{

  public function actionThreadmark()
  {
    $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

    $ftpHelper = $this->getHelper('ForumThreadPost');
    list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);


    if (!$this->_canManageThreadmarks())
    {
      throw $this->getErrorOrNoPermissionResponseException('you_do_not_have_permission_to_manage_threadmarks');
    }

    $threadmarksModel = $this->_getThreadmarksModel();
    $existingThreadmark = $threadmarksModel->getByThreadIdAndPostId($thread['thread_id'], $post['post_id']);

    if ($this->_request->isPost())
    {
      $label = $this->_input->filterSingle('label', XenForo_Input::STRING);
      if (!$label)
      {
        return $this->responseError(new XenForo_Phrase('please_enter_label_for_threadmark'));
      }

      if ($existingThreadmark) {
        if ($this->isConfirmedPost()) {
          $threadmarksModel->deleteThreadmark($existingThreadmark);
          $phrase = 'threadmark_deleted';
          XenForo_Model_Log::logModeratorAction('post', $post, 'delete_threadmark', array(), $thread);
        } else {
          $threadmarksModel->setThreadMark($thread['thread_id'], $post['post_id'], $label);
          $phrase = 'threadmark_updated';
          XenForo_Model_Log::logModeratorAction('post', $post, 'update_threadmark', array(), $thread);
        }
      } else {
        $threadmarksModel->setThreadMark($thread['thread_id'], $post['post_id'], $label);
        $phrase = 'threadmark_created';
        XenForo_Model_Log::logModeratorAction('post', $post, 'create_threadmark', array(), $thread);
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
        $viewParams['threadmark'] = $existingThreadmark;
        $templateName = 'edit_threadmark';
      } else {
        $templateName = 'new_threadmark';
      }

      return $this->responseView('Sidane_Threadmarks_ViewPublic_Post_Threadmark', $templateName, $viewParams);
    }
  }

  protected function _canManageThreadmarks() {
    $visitor = XenForo_Visitor::getInstance();
    return ($visitor['user_id'] && XenForo_Permission::hasPermission($visitor['permissions'], 'forum', 'sidaneManageThreadmarks'));
  }

  protected function _getThreadmarksModel() {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}