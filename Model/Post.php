<?php


class Sidane_Threadmarks_Model_Post extends XFCP_Sidane_Threadmarks_Model_Post
{

  public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
  {
    $post = parent::preparePost($post, $thread, $forum, $nodePermissions, $viewingUser);

    $post['canManageThreadmarks'] = $this->_canManageThreadmarks($post, $thread, $forum, $null, $nodePermissions, $viewingUser);

    return $post;
  }

  protected function _canManageThreadmarks(array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
  {
    $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

    if (!$viewingUser['user_id'])
    {
      return false;
    }

    return XenForo_Permission::hasContentPermission($nodePermissions, 'sidaneManageThreadmarks');
  }

}