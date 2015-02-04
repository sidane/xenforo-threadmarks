<?php


class Sidane_Threadmarks_Model_Post extends XFCP_Sidane_Threadmarks_Model_Post
{

  public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
  {
    $post = parent::preparePost($post, $thread, $forum, $nodePermissions, $viewingUser);

    $threadmarkmodel = $this->_getThreadmarksModel();
    $post['canAddThreadmarks'] = $threadmarkmodel->canAddThreadmark($post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    $post['canEditThreadmarks'] = $threadmarkmodel->canEditThreadmark($post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    $post['canDeleteThreadmarks'] = $threadmarkmodel->canDeleteThreadmark($post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    $post['canViewThreadmarks'] = $threadmarkmodel->canViewThreadmark($thread, $null, $nodePermissions, $viewingUser);

    return $post;
  }

  public function recalculatePostPositionsInThread($threadId)
  {
    $this->_getThreadmarksModel()->recalculatePostPositionsInThread($threadId);
    return parent::recalculatePostPositionsInThread($threadId);
  }

  protected function _getThreadmarksModel() {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}