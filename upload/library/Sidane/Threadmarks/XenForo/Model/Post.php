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

    $threadmarkCategoryPositions = array();
    if (!empty($thread['threadmark_category_positions']))
    {
      $threadmarkCategoryPositions = $this
        ->_getThreadmarksModel()
        ->getThreadmarkCategoryPositionsByThread($thread);
    }

    $fetchOptions['threadmarks']['threadmark_category_positions'] = $threadmarkCategoryPositions;

    return $fetchOptions;
  }

  public function getPostsInThread($threadId, array $fetchOptions = array())
  {
    $posts = parent::getPostsInThread($threadId, $fetchOptions);

    if (empty($fetchOptions['threadmarks']))
    {
      return $posts;
    }

    // build an array of threadmarked post IDs, and an array of threadmarks
    // indexed by threadmark category and position
    $threadmarkedPostIds = array();
    $threadmarks = array();
    foreach ($posts as $postId => $post)
    {
      if (empty($post['threadmark_id']))
      {
        continue;
      }

      $threadmarkedPostIds[] = $postId;
      $threadmarks[$post['threadmark_category_id']][$post['threadmark_position']] = array(
        'post_id'                => $post['post_id'],
        'position'               => $post['position'] ? $post['position'] : 1,
        'threadmark_id'          => $post['threadmark_id'],
        'threadmark_category_id' => $post['threadmark_category_id'],
        'threadmark_position'    => $post['threadmark_position']
      );
    }

    // calculate missing threadmarks for each category
    $threadmarkCategoryPositions = $fetchOptions['threadmarks']['threadmark_category_positions'];
    $missingThreadmarks = array();
    foreach ($threadmarks as $threadmarkCategoryId => $_threadmarks)
    {
      $firstCategoryPosition = 0;
      $lastCategoryPosition = 0;
      if (!empty($threadmarkCategoryPositions[$threadmarkCategoryId]))
      {
        $lastCategoryPosition = $threadmarkCategoryPositions[$threadmarkCategoryId];
      }

      foreach ($_threadmarks as $position => $threadmark)
      {
        $prevPosition = $position - 1;
        if (
          ($prevPosition >= $firstCategoryPosition) &&
          !isset($_threadmarks[$prevPosition])
        )
        {
          $missingThreadmarks[$threadmarkCategoryId][] = $prevPosition;
        }

        $nextPosition = $position + 1;
        if (
          ($nextPosition <= $lastCategoryPosition) &&
          !isset($_threadmarks[$nextPosition])
        )
        {
          $missingThreadmarks[$threadmarkCategoryId][] = $nextPosition;
        }
      }
    }

    // resolve any missing threadmark positions
    if (!empty($missingThreadmarks))
    {
      $extraThreadmarks = $this
        ->_getThreadmarksModel()
        ->getThreadmarksByCategoryAndPosition($threadId, $missingThreadmarks);

      $threadmarks = array_replace_recursive($threadmarks, $extraThreadmarks);
    }

    foreach ($threadmarkedPostIds as $postId)
    {
      $threadmarkCategoryId = $posts[$postId]['threadmark_category_id'];
      $threadmarkPosition = $posts[$postId]['threadmark_position'];

      if (isset($threadmarks[$threadmarkCategoryId][$threadmarkPosition + 1]))
      {
        $posts[$postId]['threadmark_next'] = $threadmarks[$threadmarkCategoryId][$threadmarkPosition + 1];
      }

      if (isset($threadmarks[$threadmarkCategoryId][$threadmarkPosition - 1]))
      {
        $posts[$postId]['threadmark_previous'] = $threadmarks[$threadmarkCategoryId][$threadmarkPosition - 1];
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
