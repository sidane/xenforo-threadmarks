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
      ($thread['user_id'] == $viewingUser['user_id']) &&-
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

  public function setThreadMark(
    array $thread,
    array $post,
    $label,
    $categoryId,
    $addLast = true
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
      $position = null;

      if (!$addLast)
      {
        // get last threadmark position up to current post and add 1
        $position = $db->fetchOne(
          'SELECT threadmarks.position + 1
            FROM xf_post AS post
            JOIN threadmarks ON threadmarks.post_id = post.post_id
            WHERE post.thread_id = ?
              AND post.position < ?
              AND threadmark.threadmark_category_id = ?
            ORDER BY threadmarks.position DESC
            LIMIT 1',
          array(
            $thread['thread_id'],
            $post['position'],
            $categoryId
          )
        );
      }

      if ($position === null)
      {
        // get last threadmark position and add 1
        $position = $db->fetchOne(
          'SELECT position + 1
            FROM threadmarks
            WHERE thread_id = ?
              AND threadmark_category_id = ?
            ORDER BY position DESC
            LIMIT 1',
          array(
            $thread['thread_id'],
            $categoryId
          )
        );
      }

      if ($position === null)
      {
        // there are no threadmarks
        $position = 0;
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

  public function rebuildThreadMarkCache($threadId)
  {
    $db = $this->_getDb();

    // remove orphaned threadmarks (by post)
    $db->query(
      'DELETE FROM threadmarks
        LEFT JOIN xf_post AS post ON post.post_id = threadmarks.post_id
        WHERE threadmarks.thread_id = ? AND post.post_id IS NULL',
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
    $db->query('
      UPDATE threadmarks
        JOIN xf_post AS post ON post.post_id = threadmarks.post_id
        SET threadmarks.message_state = post.message_state
        WHERE threadmarks.thread_id = ?',
      $threadId
    );

    // recompute threadmark totals
    $db->query(
      "UPDATE xf_thread
        SET threadmark_count = (
          SELECT COUNT(threadmarks.threadmark_id)
            FROM threadmarks
            WHERE xf_thread.thread_id = threadmarks.thread_id
              AND threadmarks.message_state = 'visible'
        )
        WHERE thread_id = ?",
      $threadId
    );

    XenForo_Db::beginTransaction($db);

    // update display ordering
    $order = $this->recomputeDisplayOrder($threadId);
    $this->massUpdateDisplayOrder($threadId, $order);

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

  public function getThreadMarkByPositions($threadId, $positions)
  {
    if (empty($positions))
    {
      return array();
    }

    return $this->fetchAllKeyed("
      SELECT post.post_id, post.position, threadmark_id, threadmarks.position as threadmark_position
      FROM threadmarks
      JOIN xf_post AS post ON post.post_id = threadmarks.post_id
      WHERE threadmarks.thread_id = ? and threadmarks.position IN (" . $this->_getDb()->quote($positions) . ") and threadmarks.message_state = 'visible'
      ORDER BY threadmarks.position ASC
    ",'threadmark_position', $threadId);
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
      'threadmark_category_id',
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

  public function recomputeDisplayOrder($threadId)
  {
    $order = $this->fetchAllKeyed("
      SELECT threadmark_id as id, parent_threadmark_id as parent
      FROM threadmarks
      WHERE thread_id = ?
      ORDER BY position
    ", 'id', $threadId);

    if (empty($order))
    {
        return;
    }

    // build the tree
    $children = array();
    foreach($order as $key => &$threadmark)
    {
      $parent = $threadmark['parent'];
      if ($parent)
      {
        if (empty($order[$parent]['children']))
        {
          $order[$parent]['children'] = array();
        }
        $order[$parent]['children'][] = &$threadmark;
        $children[] = $key;
      }
    }

    // cleanup non-root level nodes
    foreach($children as $key)
    {
      unset($order[$key]);
    }

    return $order;
  }

  protected function preorderTreeTraversal($order, $parentThreadmarkId, $depth, &$position, array &$args)
  {
    foreach($order as &$item)
    {
      if (empty($item['id']))
      {
        continue;
      }
      $threadmarkId = $item['id'];

      $oldposition = $position;
      $position += 1;
      if (!empty($item['children']))
      {
        $this->preorderTreeTraversal($item['children'], $threadmarkId, $depth + 1, $position, $args);
      }

      $args['pos'][] = $threadmarkId;
      $args['pos'][] = $oldposition;
      $args['depth'][] = $threadmarkId;
      $args['depth'][] = $depth;
      $args['parent'][] = $threadmarkId;
      $args['parent'][] = $parentThreadmarkId;
    }
  }

  public function massUpdateDisplayOrder($threadId, $order)
  {
    $sqlOrder = '';
    $db = $this->_getDb();
    $args = array();

    if (!empty($order))
    {
      $position = 0;
      $this->preorderTreeTraversal($order, 0, 0, $position, $args);

      if (!empty($args))
      {
        $args = array_merge($args['pos'], $args['depth'], $args['parent']);

        if (!empty($args))
        {
          $args[] = $threadId;

          $sqlBit = str_repeat("WHEN ? THEN ? ", $position);
          $db->query('
              UPDATE threadmarks SET
                position = CASE threadmark_id ' . $sqlBit . '  ELSE 0 END,
                depth = CASE threadmark_id ' . $sqlBit . '  ELSE 0 END,
                parent_threadmark_id = CASE threadmark_id ' . $sqlBit . '  ELSE 0 END
              WHERE thread_id = ?
          ', $args);
        }
      }
    }

    $db->query("
        UPDATE xf_thread
        SET
           firstThreadmarkId = COALESCE((SELECT min(position) FROM threadmarks WHERE threadmarks.thread_id = xf_thread.thread_id and threadmarks.message_state = 'visible'), 0),
           lastThreadmarkId = COALESCE((SELECT max(position) FROM threadmarks WHERE threadmarks.thread_id = xf_thread.thread_id and threadmarks.message_state = 'visible'), 0 )
        WHERE thread_id = ?
    ", $threadId);
  }

  public function preparelistToTree($threadmarks)
  {
    $lastThreadmark = null;
    foreach($threadmarks as &$threadmark)
    {
      if ($lastThreadmark && $threadmark['depth'] != $lastThreadmark['depth'])
      {
        if ($threadmark['depth'] > $lastThreadmark['depth'])
        {
          $lastThreadmark['extraCss'] = 'parent';
          $lastThreadmark['templateHelperChild'] = '<ul>';
        }
        else
        {
          $lastThreadmark['templateHelperEnd'] = '</li>' . str_repeat('</ul></li>', $lastThreadmark['depth'] - $threadmark['depth']);
        }
      }
      else
      {
        $lastThreadmark['templateHelperEnd'] = '</li>';
      }
      $lastThreadmark = &$threadmark;
    }
    if ($lastThreadmark)
    {
      if (!isset($lastThreadmark['templateHelperEnd']))
      {
        $lastThreadmark['templateHelperEnd'] = '</li>';
      }
      if ($lastThreadmark['depth'] > 1)
      {
        $lastThreadmark['templateHelperEnd'] .= str_repeat('</ul></li>', $lastThreadmark['depth'] - 1);
      }
    }
    return $threadmarks;
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

  public function getAllThreadmarkCategories()
  {
    return $this->fetchAllKeyed(
      'SELECT *
        FROM threadmark_category
        ORDER BY display_order',
      'threadmark_category_id'
    );
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

  public function getThreadmarkCategoryOptions(
    $filterUsable = false,
    array $viewingUser = null
  ) {
    $threadmarkCategories = $this->getAllThreadmarkCategories();
    $threadmarkCategories = $this->prepareThreadmarkCategories(
      $threadmarkCategories
    );

    if ($filterUsable)
    {
      $this->standardizeViewingUserReference($viewingUser);

      foreach ($threadmarkCategories as $threadmarkCategoryId => $threadmarkCategory)
      {
        if (!$this->canUseThreadmarkCategory($threadmarkCategory, $viewingUser))
        {
          unset($threadmarkCategories[$threadmarkCategoryId]);
        }
      }
    }

    $options = array();

    foreach ($threadmarkCategories as $threadmarkCategoryId => $threadmarkCategory)
    {
      $options[$threadmarkCategoryId] = $threadmarkCategory['title'];
    }

    return $options;
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

  public function getThreadmarksInCategory($threadmarkCategoryId)
  {
    return $this->fetchAllKeyed(
      'SELECT *
        FROM threadmarks
        WHERE threadmark_category_id = ?',
      'threadmark_id',
      $threadmarkCategoryId
    );
  }
}
