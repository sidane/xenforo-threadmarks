<?php

class Sidane_Threadmarks_XenForo_Model_Post extends XFCP_Sidane_Threadmarks_XenForo_Model_Post
{
  public function preparePostJoinOptions(array $fetchOptions)
  {
    $joinOptions = parent::preparePostJoinOptions($fetchOptions);

    if (!empty($fetchOptions['includeThreadmark']))
    {
      $joinOptions['selectFields'] .= ',
        threadmarks.threadmark_id,
        threadmarks.threadmark_category_id as threadmark_category_id,
        threadmarks.label as threadmark_label,
        threadmarks.edit_count as threadmark_edit_count,
        threadmarks.user_id as threadmark_user_id,
        threadmarks.position as threadmark_position,
        threadmarks.last_edit_date as threadmark_last_edit_date,
        threadmarks.last_edit_user_id as threadmark_last_edit_user_id,
        threadmarks.threadmark_date as threadmark_threadmark_date';
      $joinOptions['joinTables'] .= '
        LEFT JOIN threadmarks ON threadmarks.post_id = post.post_id';

      $joinOptions['selectFields'] .= ',
        tm_user.username as threadmark_username';
      $joinOptions['joinTables'] .= '
        LEFT JOIN xf_user AS tm_user ON tm_user.user_id = threadmarks.user_id';
    }

    return $joinOptions;
  }

  public function getPermissionBasedPostFetchOptions(
    array $thread,
    array $forum,
    array $nodePermissions = null,
    array $viewingUser = null
  ) {
    $fetchOptions = parent::getPermissionBasedPostFetchOptions(
      $thread,
      $forum,
      $nodePermissions,
      $viewingUser
    );

    if (!empty($thread['firstThreadmarkId']) || !empty($thread['lastThreadmarkId']))
    {
      $fetchOptions['threadmarks'] = array(
        'firstThreadmarkId' => isset($thread['firstThreadmarkId']) ? $thread['firstThreadmarkId'] : 0,
        'lastThreadmarkId'  => isset($thread['lastThreadmarkId']) ? $thread['lastThreadmarkId'] : 0,
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

  public function preparePost(
    array $post,
    array $thread,
    array $forum,
    array $nodePermissions = null,
    array $viewingUser = null
  ) {
    $post = parent::preparePost(
      $post,
      $thread,
      $forum,
      $nodePermissions,
      $viewingUser
    );

    $threadmarksModel = $this->_getThreadmarksModel();

    $canViewThreadmarks = $threadmarksModel->canViewThreadmark(
      $thread,
      $forum,
      $null,
      $nodePermissions,
      $viewingUser
    );
    $canAddThreadmarks = $threadmarksModel->canAddThreadmark(
      $post,
      $thread,
      $forum,
      $null,
      $nodePermissions,
      $viewingUser
    );
    $canEditThreadmarks = false;
    $canDeleteThreadmarks = false;

    if (!empty($post['threadmark_id']))
    {
      $post['threadmark'] = array('threadmark_id' => $post['threadmark_id']);
      $threadmarksModel->remapThreadmark($post, $post['threadmark']);

      $post['threadmark'] = $threadmarksModel->prepareThreadmark(
        $post['threadmark'],
        $thread,
        $forum,
        $nodePermissions,
        $viewingUser
      );

      $canEditThreadmarks = $threadmarksModel->canEditThreadmark(
        $post['threadmark'],
        $post,
        $thread,
        $forum,
        $null,
        $nodePermissions,
        $viewingUser
      );
      $canDeleteThreadmarks = $threadmarksModel->canDeleteThreadmark(
        $post['threadmark'],
        $post,
        $thread,
        $forum,
        $null,
        $nodePermissions,
        $viewingUser
      );
    }

    $post['canViewThreadmarks'] = $canViewThreadmarks;
    $post['canAddThreadmarks'] = $canAddThreadmarks;
    $post['canEditThreadmarks'] = $canEditThreadmarks;
    $post['canDeleteThreadmarks'] = $canDeleteThreadmarks;

    return $post;
  }

  public function recalculatePostPositionsInThread($threadId)
  {
    $data = parent::recalculatePostPositionsInThread($threadId);

    $this->_getThreadmarksModel()->rebuildThreadMarkCache($threadId);

    return $data;
  }

  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
