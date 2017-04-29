<?php

class Sidane_Threadmarks_Model_Threadmarks extends XenForo_Model
{
  const FETCH_POSTS_MINIMAL = 0x01;

  public function getMenuLimit(array $thread, array $nodePermissions = null, array $viewingUser = null)
  {
    $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

    $menulimit = XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_menu_limit');
    if($menulimit > 0)
    {
      return $menulimit;
    }

    return 0;
  }

  public function canViewThreadmark(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
  {
    $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

    if (XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_manage'))
    {
      return true;
    }

    if (XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_view'))
    {
      return true;
    }

    return false;
  }

  public function canAddThreadmark(array $post = null, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
  {
    $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

    if (!$viewingUser['user_id'])
    {
      return false;
    }

    if (XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_manage'))
    {
      return true;
    }

    if ( ($thread['user_id'] == $viewingUser['user_id']) && XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_add'))
    {
      return true;
    }

    return false;
  }

  public function canDeleteThreadmark(
    array $threadmark,
    array $post,
    array $thread,
    array $forum,
    &$errorPhraseKey = '',
    array $nodePermissions = null,
    array $viewingUser = null
  ) {
    $this->standardizeViewingUserReferenceForNode(
      $thread['node_id'],
      $viewingUser,
      $nodePermissions
    );

    if (!$viewingUser['user_id'])
    {
      return false;
    }

    if (XenForo_Permission::hasContentPermission(
      $nodePermissions,
      'sidane_tm_manage'
    ))
    {
      return true;
    }

    if (
      ($thread['user_id'] == $viewingUser['user_id']) &&
      XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_delete')
    )
    {
      return true;
    }

    return false;
  }

  public function canEditThreadmark(
    array $threadmark,
    array $post,
    array $thread,
    array $forum,
    &$errorPhraseKey = '',
    array $nodePermissions = null,
    array $viewingUser = null
  ) {
    $this->standardizeViewingUserReferenceForNode(
      $thread['node_id'],
      $viewingUser,
      $nodePermissions
    );

    if (!$viewingUser['user_id'])
    {
      return false;
    }

    if (XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_manage'))
    {
      return true;
    }

    if (
      ($thread['user_id'] == $viewingUser['user_id']) &&
      XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_edit')
    )
    {
      return true;
    }

    return false;
  }

  public function prepareThreadmarks(array $threadmarks, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
  {
    $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

    foreach($threadmarks as $key => $threadmark)
    {
        $threadmarks[$key] = $this->prepareThreadmark($threadmark, $thread, $forum, $nodePermissions, $viewingUser);
    }
    return $threadmarks;
  }

  public function prepareThreadmark(
    array $threadmark,
    array $thread,
    array $forum,
    array $nodePermissions = null,
    array $viewingUser = null
  ) {
    $this->standardizeViewingUserReferenceForNode(
      $thread['node_id'],
      $viewingUser,
      $nodePermissions
    );

    $threadmark['canEdit'] = $this->canEditThreadmark(
      $threadmark,
      $threadmark,
      $thread,
      $forum,
      $null,
      $nodePermissions,
      $viewingUser
    );
    $threadmark['canDelete'] = $this->canDeleteThreadmark(
      $threadmark,
      $threadmark,
      $thread,
      $forum,
      $null,
      $nodePermissions,
      $viewingUser
    );

    if (isset($thread['thread_read_date']) || isset($forum['forum_read_date']))
    {
        $readOptions = array(0);
        if (isset($thread['thread_read_date']))
        {
          $readOptions[] = $thread['thread_read_date'];
        }
        if (isset($forum['forum_read_date']))
        {
          $readOptions[] = $forum['forum_read_date'];
        }

        $threadmark['isNew'] = (max($readOptions) < $threadmark['threadmark_date']);
    }
    else
    {
        $threadmark['isNew'] = false;
    }

    if (isset($threadmark['post_date']) || isset($threadmark['post_position']))
    {
        $threadmark['post'] = array(
            'post_id'   => $threadmark['post_id'],
            'post_date' => @$threadmark['post_date'],
            'position'  => @$threadmark['post_position'],
        );
        unset($threadmark['post_date']);
        unset($threadmark['post_position']);
    }

    return $threadmark;
  }

  public function getThreadMarkById($id)
  {
    return $this->_getDb()->fetchRow("
      SELECT *
      FROM threadmarks
      WHERE threadmark_id = ?
    ", array($id));
  }

  public function getPreviousThreadmarkByPost($categoryId, $threadId, $postPosition)
  {
    return $this->_getDb()->fetchRow("
          select threadmarks.*, post.post_id, post.position, threadmark_id, threadmarks.position as threadmark_position
          from xf_post AS post
          join threadmarks on threadmarks.post_id = post.post_id
          where post.thread_id = ? and post.position < ? and threadmarks.thread_id = ? and threadmarks.threadmark_category_id = ? and threadmarks.message_state = 'visible'
          order by post.position desc
          limit 1;
        ", array($threadId, $postPosition, $threadId, $categoryId));
  }

  public function getPreviousThreadmarkByLocation($categoryId, $threadId, $threadmarkPosition = false)
  {
    $args = array($threadId, $categoryId);
    $sql = '';
    if ($threadmarkPosition !== false)
    {
        $sql = ' and threadmarks.position < ? ';
        $args[] = $threadmarkPosition;
    }

    return $this->_getDb()->fetchRow("
          select threadmarks.*, post.post_id, post.position, threadmark_id, threadmarks.position as threadmark_position
          from threadmarks
          join xf_post AS post on  threadmarks.post_id = post.post_id
          where threadmarks.thread_id = ? and threadmarks.threadmark_category_id = ? and threadmarks.message_state = 'visible' {$sql}
          order by threadmarks.position desc
          limit 1;
        ", $args);
  }

  public function setThreadMark(
    array $thread,
    array $post,
    $label,
    $categoryId,
    $position = false,
    $resetNesting = false
  ) {
    $db = $this->_getDb();

    XenForo_Db::beginTransaction($db);

    $threadmark = $this->getByPostId($post['post_id']);

    $dw = XenForo_DataWriter::create('Sidane_Threadmarks_DataWriter_Threadmark');
    if (!empty($threadmark['threadmark_id']))
    {
      $dw->setExistingData($threadmark);
    }
    else
    {
      if ($position === false)
      {
        $prevThreadmark = $this->getPreviousThreadmarkByPost(
          $categoryId,
          $post['thread_id'],
          $post['position']
        );

        if (isset($prevThreadmark['threadmark_position']))
        {
           $position = $prevThreadmark['threadmark_position'] + 1;

           if (!$resetNesting)
           {
             $dw->set('depth', $prevThreadmark['depth']);
             $dw->set(
               'parent_threadmark_id',
               $prevThreadmark['parent_threadmark_id']
             );
           }
        }

        if ($position === false)
        {
          $prevThreadmark = $this->getPreviousThreadmarkByLocation(
            $categoryId,
            $thread['thread_id']
          );

          if (isset($prevThreadmark['threadmark_position']))
          {
            $position = $prevThreadmark['threadmark_position'] + 1;

            if (!$resetNesting)
            {
               $dw->set('depth', $prevThreadmark['depth']);
               $dw->set(
                 'parent_threadmark_id',
                 $prevThreadmark['parent_threadmark_id']
               );
             }
          }
        }
      }
      else if (!$resetNesting)
      {
        $prevThreadmark = $this->getPreviousThreadmarkByLocation(
          $categoryId,
          $post['thread_id'],
          $position
        );

        if (isset($prevThreadmark['position']))
        {
           $dw->set('depth', $prevThreadmark['depth']);
           $dw->set(
             'parent_threadmark_id',
             $prevThreadmark['parent_threadmark_id']
           );
        }
      }

      if ($position === false)
      {
        $position = 1;
      }
      $dw->set('user_id', XenForo_Visitor::getUserId());
      $dw->set('post_id', $post['post_id']);
      $dw->set('position', $position);
    }

    $dw->set('thread_id', $thread['thread_id']);
    $dw->set('label', $label);
    $dw->set('threadmark_category_id', $categoryId);
    $dw->set('message_state', $post['message_state']);
    $dw->setExtraData(
      Sidane_Threadmarks_DataWriter_Threadmark::DATA_THREAD,
      $thread
    );
    $dw->save();

    XenForo_Db::commit($db);

    return true;
  }

  public function deleteThreadMark($threadmark)
  {
    $db = $this->_getDb();
    if (empty($threadmark['threadmark_id']))
    {
        XenForo_Error::debug("Require a threadmark_id in:".var_export($threadmark, true));
        return;
    }

    XenForo_Db::beginTransaction($db);

    $dw = XenForo_DataWriter::create("Sidane_Threadmarks_DataWriter_Threadmark");
    $dw->setExistingData($threadmark['threadmark_id']);
    $dw->delete();

    XenForo_Db::commit($db);

    return true;
  }

  public function rebuildThreadmarkCache($threadId)
  {
    $db = $this->_getDb();

    // remove orphaned threadmarks (by post)
    $db->query(
      'DELETE threadmarks
        FROM threadmarks
        LEFT JOIN xf_post AS post ON (post.post_id = threadmarks.post_id)
        WHERE threadmarks.thread_id = ?
          AND post.post_id IS NULL',
      $threadId
    );

    // push threadmarks onto the correct thread (aka potentially not this one)
    $db->query(
      'UPDATE threadmarks
        JOIN xf_post AS post ON post.post_id = threadmarks.post_id
        SET threadmarks.thread_id = post.thread_id
        WHERE threadmarks.thread_id = ?',
      $threadId
    );

    // pull threadmarks onto the correct thread (aka potentially this one)
    // this can be fairly slow depending on the thread length
    $db->query(
      'UPDATE threadmarks
        JOIN xf_post AS post ON post.post_id = threadmarks.post_id
        SET threadmarks.thread_id = post.thread_id
        WHERE post.thread_id = ?',
      $threadId
    );

    // ensure resync attributes off the xf_post table
    $db->query(
      'UPDATE threadmarks
        JOIN xf_post AS post ON post.post_id = threadmarks.post_id
        SET threadmarks.message_state = post.message_state
        WHERE threadmarks.thread_id = ?',
      $threadId
    );

    XenForo_Db::beginTransaction($db);

    // update display ordering
    $threadmarks = $this->recomputeDisplayOrder($threadId);
    $this->massUpdateDisplayOrder($threadId, null, $threadmarks);

    XenForo_Db::commit($db);
  }

  public function getThreadIdsWithThreadMarks($thread_id, $limit = 0)
  {
    $db = $this->_getDb();
    return $db->fetchCol($this->limitQueryResults("
      SELECT distinct thread_id
      FROM threadmarks
      where thread_id > ?
      ORDER BY threadmarks.thread_id ASC
    ",$limit, 0), $thread_id);
  }

  public function getRecentByThreadId($threadId, $limit = 0, $offset = 0) {
    return $this->fetchAllKeyed($this->limitQueryResults("
      SELECT threadmarks.*, post.post_date, post.position as post_position
      FROM threadmarks
      JOIN xf_post AS post ON post.post_id = threadmarks.post_id
      WHERE threadmarks.thread_id = ? and threadmarks.message_state = 'visible'
      ORDER BY threadmarks.position DESC
    ",$limit, $offset), 'post_id', $threadId);
  }

  public function getByPostId($postId) {
    return $this->_getDb()->fetchRow("
      SELECT *
      FROM threadmarks
      WHERE post_id = ?
    ", array($postId));
  }


  public function getThreadMarkIdsInRange($start, $limit)
  {
    $db = $this->_getDb();

    return $db->fetchCol($db->limit('
      SELECT threadmark_id
      FROM threadmarks
      WHERE threadmark_id > ?
      ORDER BY threadmark_id
    ', $limit), $start);
  }

  public function getThreadMarkByIds($Ids)
  {
    if (empty($Ids))
    {
      return array();
    }

    return $this->fetchAllKeyed("
      SELECT *
      FROM threadmarks
      WHERE threadmark_id IN (" . $this->_getDb()->quote($Ids) . ")
    ",'threadmark_id');
  }

  public function getThreadmarksByCategory($threadmarkCategoryId)
  {
    return $this->fetchAllKeyed(
      'SELECT *
        FROM threadmarks
        WHERE threadmark_category_id = ?',
      'threadmark_id',
      $threadmarkCategoryId
    );
  }

  public function getThreadmarksByCategoryAndPosition(
    $threadId,
    $threadmarkCategories
  )
  {
    if (empty($threadmarkCategories))
    {
      return array();
    }

    $clauses = array();
    foreach ($threadmarkCategories as $threadmarkCategoryId => $positions)
    {
      $positions = $this->_getDb()->quote($positions);
      $clauses[] = "(threadmarks.threadmark_category_id = {$threadmarkCategoryId}
        AND threadmarks.position IN ({$positions}))";
    }
    $clauses = '('.implode(' OR ', $clauses).')';

    $threadmarks = $this->fetchAllKeyed(
      "SELECT threadmarks.threadmark_id, threadmarks.threadmark_category_id, threadmarks.position AS threadmark_position, post.post_id, post.position
        FROM threadmarks
        JOIN xf_post AS post ON post.post_id = threadmarks.post_id
        WHERE threadmarks.thread_id = ?
          AND {$clauses}
          AND threadmarks.message_state = 'visible'
        ORDER BY threadmarks.position",
      'threadmark_id',
      $threadId
    );

    $groupedThreadmarks = array();
    foreach ($threadmarks as $threadmark)
    {
      $threadmarkCategoryId = $threadmark['threadmark_category_id'];
      $threadmarkPosition = $threadmark['threadmark_position'];

      $groupedThreadmarks[$threadmarkCategoryId][$threadmarkPosition] = $threadmark;
    }

    return $groupedThreadmarks;
  }

  public function getByThreadIdWithMinimalPostData($threadId)
  {
    return $this->getThreadmarksByThread(
      $threadId,
      array( 'join' => self::FETCH_POSTS_MINIMAL)
    );
  }

  public function getThreadmarksByThread($threadId, array $fetchOptions = array())
  {
    $joinOptions = $this->prepareThreadmarkJoinOptions($fetchOptions);

    return $this->fetchAllKeyed("
      SELECT threadmarks.*
        " . $joinOptions['selectFields'] . "
      FROM threadmarks
        " . $joinOptions['joinTables'] . "
      WHERE threadmarks.thread_id = ?
        AND threadmarks.message_state = 'visible'
      ORDER BY threadmarks.position ASC
    ", 'post_id', $threadId);
  }

  public function getThreadmarksByThreadAndCategory(
    $threadId,
    $threadmarkCategoryId,
    array $fetchOptions = array()
  ) {
    $joinOptions = $this->prepareThreadmarkJoinOptions($fetchOptions);

    return $this->fetchAllKeyed(
      "SELECT threadmarks.*
          {$joinOptions['selectFields']}
        FROM threadmarks
          {$joinOptions['joinTables']}
        WHERE threadmarks.thread_id = ?
          AND threadmarks.threadmark_category_id = ?
          AND threadmarks.message_state = 'visible'
        ORDER BY threadmarks.position ASC",
      'post_id',
      array(
        $threadId,
        $threadmarkCategoryId
      )
    );
  }

  public function prepareThreadmarkJoinOptions(array $fetchOptions)
  {
    $selectFields = '';
    $joinTables = '';

    if (!empty($fetchOptions['join']))
    {
      if ($fetchOptions['join'] & self::FETCH_POSTS_MINIMAL)
      {
        $selectFields .= ',
          post.post_date, post.position as post_position';
        $joinTables .= '
          JOIN xf_post AS post ON
            (post.post_id = threadmarks.post_id)';
      }
    }

    return array(
      'selectFields' => $selectFields,
      'joinTables'   => $joinTables
    );
  }

  /**
   * Maps threadmark data from the source array to the correct fields in the
   * destination array.
   *
   * @param array $source
   * @param array $dest
   */
  public function remapThreadmark(array &$source, array &$dest)
  {
    $prefix = 'threadmark';
    $remap = array(
      'user_id',
      'username',
      'threadmark_date',
      'position',
      'message_state',
      'edit_count',
      'last_edit_date',
      'last_edit_user_id',
      'label'
    );

    foreach($remap as $remapItem)
    {
      $key = $prefix .'_'. $remapItem;
      if (isset($source[$key]))
      {
        $dest[$remapItem] = $source[$key];
        unset($source[$key]);
      }
    }

    if (isset($source['threadmark_category_id']))
    {
      $dest['threadmark_category_id'] = $source['threadmark_category_id'];
    }
  }

  public function getNextThreadmark($threadmark)
  {
    return $this->_getDb()->fetchRow(
      "SELECT *
        FROM threadmarks
        WHERE thread_id = ?
          AND threadmark_category_id = ?
          AND position > ?
          AND message_state = 'visible'
        ORDER BY position
        LIMIT 1",
      array(
        $threadmark['thread_id'],
        $threadmark['threadmark_category_id'],
        $threadmark['position']
      )
    );
  }

  public function getPreviousThreadmark($threadmark)
  {
    return $this->_getDb()->fetchRow(
      "SELECT *
        FROM threadmarks
        WHERE thread_id = ?
          AND threadmark_category_id = ?
          AND position < ?
          AND message_state = 'visible'
        ORDER BY position DESC
        LIMIT 1",
      array(
        $threadmark['thread_id'],
        $threadmark['threadmark_category_id'],
        $threadmark['position']
      )
    );
  }

  public function massUpdateDisplayOrder(
    $threadId,
    $threadmarkCategoryId,
    array $threadmarks
  )
  {
    if (empty($threadmarks))
    {
      return;
    }

    $positions = array();
    $depths = array();
    $parentThreadmarkIds = array();
    foreach ($threadmarks as $threadmarkId => $threadmark)
    {
      $positions[] = $threadmarkId;
      $positions[] = $threadmark['position'];

      $depths[] = $threadmarkId;
      $depths[] = $threadmark['depth'];

      $parentThreadmarkIds[] = $threadmarkId;
      $parentThreadmarkIds[] = $threadmark['parent_threadmark_id'];
    }

    $conditions = str_repeat("WHEN ? THEN ? ", count($threadmarks));
    $parameters = array_merge(
      $positions,
      $depths,
      $parentThreadmarkIds,
      array($threadId)
    );

    if ($threadmarkCategoryId === null)
    {
      $this->_getDb()->query(
        "UPDATE threadmarks
          SET position = CASE threadmark_id {$conditions} ELSE 0 END,
            depth = CASE threadmark_id {$conditions} ELSE 0 END,
            parent_threadmark_id = CASE threadmark_id {$conditions} ELSE 0 END
          WHERE thread_id = ?",
        $parameters
      );
    }
    else
    {
      $parameters[] = $threadmarkCategoryId;

      $this->_getDb()->query(
        "UPDATE threadmarks
          SET position = CASE threadmark_id {$conditions} ELSE 0 END,
            depth = CASE threadmark_id {$conditions} ELSE 0 END,
            parent_threadmark_id = CASE threadmark_id {$conditions} ELSE 0 END
          WHERE thread_id = ?
            AND threadmark_category_id = ?",
        $parameters
      );
    }

    $this->updateThreadmarkDataForThread($threadId);
  }

  public function recomputeDisplayOrder($threadId)
  {
    $threadmarks = $this->fetchAllKeyed(
      'SELECT threadmark_id, threadmark_category_id, parent_threadmark_id
        FROM threadmarks
        WHERE thread_id = ?
        ORDER BY position',
      'threadmark_id',
      $threadId
    );

    if (empty($threadmarks))
    {
        return;
    }

    $groupedThreadmarks = array();
    foreach ($threadmarks as $threadmarkId => $threadmark)
    {
      $threadmarkCategoryId = $threadmark['threadmark_category_id'];
      $groupedThreadmarks[$threadmarkCategoryId][$threadmarkId] = $threadmark;
    }

    $categoryTrees = array();
    foreach ($groupedThreadmarks as $threadmarkCategoryId => $threadmarks)
    {
      $categoryTrees[$threadmarkCategoryId] = $this->buildThreadmarkTree(
        $threadmarks
      );
    }

    $threadmarks = array();
    foreach ($categoryTrees as $threadmarkCategoryId => $threadmarkTree)
    {
      $threadmarks += $this->flattenThreadmarkTree($threadmarkTree);
    }

    return $threadmarks;
  }

  public function buildThreadmarkTree(
    array &$threadmarks,
    $parentThreadmarkId = 0,
    $depth = 0,
    &$position = 1
  ) {
    $branch = array();

    foreach ($threadmarks as $threadmarkId => $threadmark)
    {
      if ($threadmark['parent_threadmark_id'] == $parentThreadmarkId)
      {
        $threadmark['depth'] = $depth;
        $threadmark['position'] = $position;

        $position++;

        $children = $this->buildThreadmarkTree(
          $threadmarks,
          $threadmarkId,
          $depth + 1,
          $position
        );

        if (!empty($children))
        {
          $threadmark['children'] = $children;
        }

        $branch[$threadmarkId] = $threadmark;
        unset($threadmarks[$threadmarkId]);
      }
    }

    return $branch;
  }

  public function flattenThreadmarkTree(array $threadmarkTree)
  {
    $threadmarks = array();

    foreach ($threadmarkTree as $threadmarkId => $threadmark)
    {
      $threadmarks[$threadmarkId] = $threadmark;

      if (!empty($threadmark['children']))
      {
        $threadmarks += $this->flattenThreadmarkTree($threadmark['children']);
        unset($threadmarks[$threadmarkId]['children']);
      }
    }

    return $threadmarks;
  }

  public function getAllThreadmarkCategories()
  {
    return $this->fetchAllKeyed(
      'SELECT *
        FROM threadmark_category
        ORDER BY display_order',
      'threadmark_category_id'
    );
  }

  public function getThreadmarkCategoryById($threadmarkCategoryId)
  {
    return $this->_getDb()->fetchRow(
      'SELECT *
        FROM threadmark_category
        WHERE threadmark_category_id = ?',
      $threadmarkCategoryId
    );
  }

  public function getDefaultThreadmarkCategory(array $viewingUser = null)
  {
    $this->standardizeViewingUserReference($viewingUser);

    $categories = $this->getAllThreadmarkCategories();
    foreach ($categories as $category)
    {
        if ($this->canUseThreadmarkCategory($category, $viewingUser))
        {
           return $category;
        }
    }

    return null;
  }

  public function prepareThreadmarkCategory(array $threadmarkCategory)
  {
    if (!empty($threadmarkCategory['threadmark_category_id']))
    {
      $threadmarkCategory['title'] = new XenForo_Phrase(
        $this->getThreadmarkCategoryTitlePhraseName(
          $threadmarkCategory['threadmark_category_id']
        )
      );
    }

    return $threadmarkCategory;
  }

  public function prepareThreadmarkCategories(array $threadmarkCategories)
  {
    return array_map(
      array($this, 'prepareThreadmarkCategory'),
      $threadmarkCategories
    );
  }

  public function canUseThreadmarkCategory(
    array $threadmarkCategory,
    array $viewingUser = null
  )
  {
    if (empty($threadmarkCategory) ||
      empty($threadmarkCategory['allowed_user_group_ids']))
    {
      return false;
    }

    $this->standardizeViewingUserReference($viewingUser);

    $allowedUserGroupIds = explode(
      ',',
      $threadmarkCategory['allowed_user_group_ids']
    );
    $secondaryUserGroupIds = explode(
        ',',
        $viewingUser['secondary_group_ids']
    );
    $matchingSecondaryUserGroupIds = array_intersect(
        $allowedUserGroupIds,
        $secondaryUserGroupIds
      );

    if (in_array($viewingUser['user_group_id'], $allowedUserGroupIds) ||
        !empty($matchingSecondaryUserGroupIds))
    {
        return true;
    }

    return false;
  }

  public function filterUsableThreadmarkCategories(
    array $threadmarkCategories,
    array $viewingUser = null
  ) {
    $this->standardizeViewingUserReference($viewingUser);

    foreach ($threadmarkCategories as $threadmarkCategoryId => $threadmarkCategory)
    {
      if (!$this->canUseThreadmarkCategory($threadmarkCategory, $viewingUser))
      {
        unset($threadmarkCategories[$threadmarkCategoryId]);
      }
    }

    return $threadmarkCategories;
  }

  public function getThreadmarkCategoryOptions(
    array $threadmarkCategories,
    $filterUsable = false
  ) {
    $threadmarkCategories = $this->getAllThreadmarkCategories();
    $threadmarkCategories = $this->prepareThreadmarkCategories(
      $threadmarkCategories
    );

    if ($filterUsable)
    {
      $threadmarkCategories = $this->filterUsableThreadmarkCategories(
        $threadmarkCategories
      );
    }

    $options = array();

    foreach ($threadmarkCategories as $threadmarkCategoryId => $threadmarkCategory)
    {
      $options[$threadmarkCategoryId] = $threadmarkCategory['title'];
    }

    return $options;
  }

  public function getThreadmarkCategoryTitlePhraseName($threadmarkCategoryId)
  {
    return "sidane_threadmarks_category_{$threadmarkCategoryId}_title";
  }

  public function getThreadmarkCategoryMasterTitlePhraseName($threadmarkCategoryId)
  {
    $phraseName = $this->getThreadmarkCategoryTitlePhraseName($threadmarkCategoryId);

    return $this
      ->getModelFromCache('XenForo_Model_Phrase')
      ->getMasterPhraseValue($phraseName);
  }

  /**
   * Updates the 'threadmark_category_positions' column of a thread to the
   * current maximum position of each threadmark category. This should be called
   * inside of a transaction to avoid race conditions and inconsistencies.
   *
   * @param $threadId
   */
  public function updateThreadmarkDataForThread($threadId)
  {
    $threadmarkCategoryPositions = $this->fetchAllKeyed(
      'SELECT threadmark_category_id, MAX(position) AS position
        FROM threadmarks
        WHERE thread_id = ?
        GROUP BY threadmark_category_id',
      'threadmark_category_id',
      $threadId
    );

    $threadmarkCategoryPositions = array_map(
      function ($threadmarkCategory)
      {
        return $threadmarkCategory['position'];
      },
      $threadmarkCategoryPositions
    );

    $threadmarkCount = array_sum($threadmarkCategoryPositions);
    $threadmarkCategoryPositions = json_encode($threadmarkCategoryPositions);

    $this->_getDb()->query(
      'UPDATE xf_thread
        SET threadmark_count = ?,
          threadmark_category_positions = ?
        WHERE thread_id = ?',
      array(
        $threadmarkCount,
        $threadmarkCategoryPositions,
        $threadId
      )
    );
  }

  public function getThreadmarkCategoryPositionsByThread(array $thread)
  {
    $threadmarkCategoryPositions = @json_decode(
      $thread['threadmark_category_positions'],
      true
    );

    if (empty($threadmarkCategoryPositions))
    {
      $threadmarkCategoryPositions = array();
    }

    return $threadmarkCategoryPositions;
  }

  /**
   * @return XenForo_Model_Thread
   */
  protected function _getThreadModel()
  {
    return $this->getModelFromCache('XenForo_Model_Thread');
  }
}
