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

  public function canDeleteThreadmark(array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
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

    if (($thread['user_id'] == $viewingUser['user_id']) && XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_delete'))
    {
      return true;
    }

    return false;
  }

  public function canEditThreadmark(array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
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

    if (($thread['user_id'] == $viewingUser['user_id']) && XenForo_Permission::hasContentPermission($nodePermissions, 'sidane_tm_edit'))
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

  public function prepareThreadmark(array $threadmark, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
  {
    $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

    $threadmark['canEdit'] = $this->canEditThreadmark($threadmark, $thread, $forum, $null, $nodePermissions, $viewingUser);
    $threadmark['canDelete'] = $this->canDeleteThreadmark($threadmark, $thread, $forum, $null, $nodePermissions, $viewingUser);

    if (isset($thread['thread_read_date']) || isset($forum['forum_read_date']))
    {
        $readOptions = array(0);
        if (isset($thread['thread_read_date'])) { $readOptions[] = $thread['thread_read_date']; }
        if (isset($forum['forum_read_date'])) { $readOptions[] = $forum['forum_read_date']; }

        $threadmark['isNew'] = (max($readOptions) < $threadmark['threadmark_date']);
    }
    else
    {
        $threadmark['isNew'] = false;
    }

    if (isset($threadmark['post_date']) || isset($threadmark['post_position']))
    {
        $threadmark['post'] = array
        (
            'post_id' => $threadmark['post_id'],
            'post_date' => @$threadmark['post_date'],
            'position' => @$threadmark['post_position'],
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

  public function setThreadMark($thread, $post, $label, $add_last = true) {
    $db = $this->_getDb();

    XenForo_Db::beginTransaction($db);

    $threadmark = $this->getByPostId($post['post_id']);
    $dw = XenForo_DataWriter::create("Sidane_Threadmarks_DataWriter_Threadmark");
    if (!empty($threadmark['threadmark_id']))
    {
      $dw->setExistingData($threadmark);
    }
    else
    {
      $position = null;
      if (!$add_last)
      {
        $position = $db->fetchOne("
          select threadmarks.position + 1
          from xf_post AS post
          join threadmarks on threadmarks.post_id = post.post_id
          where post.thread_id = ? and post.position < ?
          order by threadmarks.position desc
          limit 1;
        ", array($thread['thread_id'], $post['position']));
      }
      if ($position === null)
      {
        $position = $db->fetchOne("
          select position + 1
          from threadmarks
          where thread_id = ?
          order by position desc
          limit 1;
        ", array($thread['thread_id']));
      }
      if ($position === null)
      {
        $position = 0;
      }
      $dw->set('user_id', XenForo_Visitor::getUserId());
      $dw->set('post_id', $post['post_id']);
      $dw->set('position', $position);
    }
    $dw->set('thread_id', $thread['thread_id']);
    $dw->set('label', $label);
    $dw->set('message_state', $post['message_state']);
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

  public function rebuildThreadMarkCache($thread_id)
  {
    $db = $this->_getDb();

    // remove orphaned threadmarks (by post)
    $db->query('
        DELETE `threadmarks`
        FROM `threadmarks`
        LEFT JOIN xf_post AS post on post.post_id = threadmarks.post_id
        where `threadmarks`.thread_id = ? and post.post_id is null;
    ', $thread_id);

    // push threadmarks onto the correct thread (aka potentially not this one)
    $db->query('
update `threadmarks` marks
join xf_post AS post on post.post_id = marks.post_id
set marks.thread_id = post.thread_id
where marks.thread_id = ?
    ', $thread_id);

    // pull threadmarks onto the correct thread (aka potentially this one). This can be fairly slow depending on the thread length.
    $db->query('
update `threadmarks` marks
join xf_post AS post on post.post_id = marks.post_id
set marks.thread_id = post.thread_id
where post.thread_id = ?
    ', $thread_id);

    // ensure resync attributes off the xf_post table
    $db->query('
        update `threadmarks` marks
        join xf_post AS post on post.post_id = marks.post_id
        set marks.message_state = post.message_state
        where marks.thread_id = ?
    ', $thread_id);

    // recompute threadmark totals
    $db->query("
        UPDATE xf_thread
        SET threadmark_count = (SELECT count(threadmarks.threadmark_id)
                                FROM threadmarks
                                where xf_thread.thread_id = threadmarks.thread_id and threadmarks.message_state = 'visible')
        WHERE thread_id = ?
    ", $thread_id);

    XenForo_Db::beginTransaction($db);

    // update display ordering
    $order = $this->recomputeDisplayOrder($thread_id);
    $this->massUpdateDisplayOrder($thread_id, $order);

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

  public function remapThreadmark(array &$source, array &$dest)
  {
    $prefix = 'threadmark';
    $remap = array('label', 'edit_count', 'user_id', 'username', 'last_edit_date', 'last_edit_user_id', 'position', 'threadmark_date', 'message_state');
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
    return $this->_getDb()->fetchRow("
      select threadmarks.*
      from threadmarks
      where thread_id = ? and position > ? and
            threadmarks.message_state = 'visible'
      order by position
      limit 1
    ", array($threadmark['thread_id'], $threadmark['position']));
  }

  public function getPreviousThreadmark($threadmark)
  {
    return $this->_getDb()->fetchRow("
      select threadmarks.*
      from threadmarks
      where threadmarks.thread_id = ? and position < ? and
            threadmarks.message_state = 'visible'
      order by position desc
      limit 1
    ", array($threadmark['thread_id'], $threadmark['position']));
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
}
