<?php


class Sidane_Threadmarks_Model_Post extends XFCP_Sidane_Threadmarks_Model_Post
{
  const FETCH_THREADMARKS = 0x80000; // hope this doesn't conflict

  public function preparePostJoinOptions(array $fetchOptions)
  {
    $joinOptions = parent::preparePostJoinOptions($fetchOptions);

    if (!empty($fetchOptions['join']))
    {
      if ($fetchOptions['join'] & Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS)
      {
        $joinOptions['selectFields'] .= ',
          threadmarks.threadmark_id, threadmarks.label as threadmark_label
        ';
        $joinOptions['joinTables'] .= '
          LEFT JOIN threadmarks  ON
            threadmarks.post_id = post.post_id
        ';
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
      $post['threadmark'] = array
      (
        'threadmark_id' => $post['threadmark_id'],
        'label' => $post['threadmark_label'],
      );
      unset($post['threadmark_id']);
      unset($post['threadmark_label']);
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
