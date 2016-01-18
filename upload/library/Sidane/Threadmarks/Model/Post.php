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
          threadmarks.threadmark_date as threadmark_threadmark_date,
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

    // find the first & last threadmarks on this page,
    // this allows detection of if we need to fetch more threadmarks to resolve links
    $threadmarks = array();
    $threadmarkedPosts = array();
    foreach ($posts AS $postId => &$post)
    {
      if (empty($post['threadmark_id']))
      {
        continue;
      }
      $threadmarkedPosts[] = $postId;
      $threadmarks[$post['threadmark_position']] = array
      (
        'post_id' => $post['post_id'],
        'position' => $post['position'] ? $post['position'] : 1,
        'threadmark_id' => $post['threadmark_id'],
        'threadmark_position' => $post['threadmark_position'],
      );
    }

    $firstThreadmark = $fetchOptions['threadmarks']['firstThreadmarkId'];
    $lastThreadmark = $fetchOptions['threadmarks']['lastThreadmarkId'];
    $missingThreadmarkPositions = array();
    foreach ($threadmarks AS $position => &$threadmark)
    {
      $prevPosition = $position -1;
      if ($prevPosition >= $firstThreadmark && !isset($threadmarks[$prevPosition]))
      {
        $missingThreadmarkPositions[$prevPosition] = true;

      }
      $nextPosition = $position + 1;
      if ($nextPosition <= $lastThreadmark && !isset($threadmarks[$nextPosition]))
      {
        $missingThreadmarkPositions[$nextPosition] = true;
      }
    }
    // resolve any missing threadmark positions
    if ($missingThreadmarkPositions)
    {
        $missingThreadmarkPositions = array_keys($missingThreadmarkPositions);
        $extraThreadmarks = $this->_getThreadmarksModel()->getThreadMarkByPositions($threadId, $missingThreadmarkPositions);
        $threadmarks = $threadmarks + $extraThreadmarks;
    }

    foreach ($threadmarkedPosts AS $postId)
    {
        $position = $posts[$postId]['threadmark_position'];
        if (isset($threadmarks[$position + 1]))
        {
            $posts[$postId]['threadmark_next'] = $threadmarks[$position + 1];
        }
        if (isset($threadmarks[$position - 1]))
        {
            $posts[$postId]['threadmark_previous'] = $threadmarks[$position - 1];
        }
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
    $post['canViewThreadmarks'] = $threadmarkmodel->canViewThreadmark($thread, $forum, $null, $nodePermissions, $viewingUser);

    if (!empty($post['threadmark_id']))
    {
      $post['threadmark'] = array('threadmark_id' => $post['threadmark_id']);
      $threadmarkmodel->remapThreadmark($post, $post['threadmark']);
      $post['threadmark'] = $threadmarkmodel->prepareThreadmark($post['threadmark'], $thread, $forum, $nodePermissions, $viewingUser);
    }

    return $post;
  }

  public function recalculatePostPositionsInThread($threadId)
  {
    $ret = parent::recalculatePostPositionsInThread($threadId);
    $this->_getThreadmarksModel()->rebuildThreadMarkCache($threadId);
    return $ret;
  }

  protected function _getThreadmarksModel() {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
