<?php


class Sidane_Threadmarks_Model_Post extends XFCP_Sidane_Threadmarks_Model_Post
{
  const FETCH_THREADMARKS = 0x80000; // hope this doesn't conflict
  const FETCH_THREADMARKS_FULL = 0x180000; // this includes FETCH_THREADMARKS

  public function preparePostJoinOptions(array $fetchOptions)
  {
    $joinOptions = parent::preparePostJoinOptions($fetchOptions);

    if (!empty($fetchOptions['join']))
    {
      if ($fetchOptions['join'] & Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS)
      {
        $joinOptions['selectFields'] .= ',
          threadmarks.threadmark_id, threadmarks.label as threadmark_label, threadmarks.edit_count as threadmark_edit_count,
          threadmarks.user_id as threadmark_user_id, threadmarks.position as threadmark_position
        ';
        $joinOptions['joinTables'] .= '
          LEFT JOIN threadmarks  ON
            threadmarks.post_id = post.post_id
        ';

        if (($fetchOptions['join'] & Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS_FULL) == Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS_FULL)
        {
          $joinOptions['selectFields'] .= ',
            threadmarks.last_edit_date as threadmark_last_edit_date,
            threadmarks.last_edit_user_id as threadmark_last_edit_user_id,
            threadmarks.post_date as threadmark_post_date,
            tm_user.username as threadmark_username
          ';
          
          $joinOptions['joinTables'] .= '
            LEFT JOIN xf_user tm_user  ON
              tm_user.user_id = threadmarks.user_id
          ';
        }
      }
    }

    return $joinOptions;
  }

  public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
  {
    $post = parent::preparePost($post, $thread, $forum, $nodePermissions, $viewingUser);

    $threadmarkmodel = $this->_getThreadmarksModel();
    $post['canAddThreadmarks'] = $threadmarkmodel->canAddThreadmark($post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    $post['canEditThreadmarks'] = $threadmarkmodel->canEditThreadmark($post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    $post['canDeleteThreadmarks'] = $threadmarkmodel->canDeleteThreadmark($post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    $post['canViewThreadmarks'] = $threadmarkmodel->canViewThreadmark($thread, $null, $nodePermissions, $viewingUser);

    if (!empty($post['threadmark_id']))
    {
      $post['threadmark'] = array('threadmark_id' => $post['threadmark_id']);
      $threadmarkmodel->remapThreadmark($post, $post['threadmark']);
    }

    return $post;
  }

  public function recalculatePostPositionsInThread($threadId)
  {
    $ret = parent::recalculatePostPositionsInThread($threadId);
    $this->_getThreadmarksModel()->recalculatePositionsInThread($threadId);
    return $ret;
  }

  protected function _getThreadmarksModel() {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
