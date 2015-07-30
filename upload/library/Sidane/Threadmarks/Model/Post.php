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

    return $joinOptions;
  }

  public function getPermissionBasedPostFetchOptions(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
  {
    $fetchOptions = parent::getPermissionBasedPostFetchOptions($thread, $forum, $nodePermissions, $viewingUser);

    if (!empty($thread['firstThreadmarkId']) || !empty($thread['lastThreadmarkId']))
    {
      $fetchOptions['threadmarks'] = array
      (
        'firstThreadmarkId' => $thread['firstThreadmarkId'],
        'lastThreadmarkId' => $thread['lastThreadmarkId'],
      );
    }

    return $fetchOptions;
  }

  public function getPostsInThread($threadId, array $fetchOptions = array())
  {
    $posts = parent::getPostsInThread($threadId, $fetchOptions);

    if (empty($fetchOptions['threadmarks']))
    {
      return $posts;
    }

    // wire up the previous & next threadmark links. Except for the very first & very last threadmark links
    $previous_link = null;
    $threadmarks = array();
    foreach ($posts AS &$post)
    {
      if (empty($post['threadmark_id']))
      {
        continue;
      }

      $post['threadmark_previous'] = $previous_link;
      $threadmarks[] = $previous_link = array
      (
        'post_id' => $post['post_id'],
        'position' => $post['position'] ? $post['position'] : 1,
        'threadmark_id' => $post['threadmark_id'],
        'threadmark_position' => $post['threadmark_position'],
      );
    }

    $last_index = count($threadmarks) - 1;
    if ($last_index == -1)
    {
      return $posts;
    }
    for ($i = $last_index; $i >= 0; $i--)
    {
      $nextId = $i + 1;
      $posts[$threadmarks[$i]['post_id']]['threadmark_next'] = isset($threadmarks[$nextId]) ? $threadmarks[$nextId] : null;
    }

    // Query for the first very or very last threadmark links.
    $threadmarkmodel = $this->_getThreadmarksModel();
    $lastThreadmark = $threadmarks[$last_index];
    if ($lastThreadmark['threadmark_id'] != $fetchOptions['threadmarks']['lastThreadmarkId'])
    {
      $posts[$lastThreadmark['post_id']]['threadmark_next'] = $threadmarkmodel->getNextThreadmark(array('thread_id' => $threadId, 'position' => $lastThreadmark['threadmark_position']));
    }

    $firstThreadmark = $threadmarks[0];
    if ($firstThreadmark['threadmark_id'] != $fetchOptions['threadmarks']['firstThreadmarkId'])
    {
      $posts[$firstThreadmark['post_id']]['threadmark_previous'] = $threadmarkmodel->getPreviousThreadmark(array('thread_id' => $threadId, 'position' => $firstThreadmark['threadmark_position']));
    }

    return $posts;
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
