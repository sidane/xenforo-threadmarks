<?php

class Sidane_Threadmarks_Model_Threadmarks extends XenForo_Model
{

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
  
  public function canViewThreadmark(array $thread, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
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

  public function canAddThreadmark(array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
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

  public function setThreadMark($thread_id, $post_id, $label) {
    $db = $this->_getDb();

    XenForo_Db::beginTransaction($db);

    $stmt =  $db->query('
      INSERT INTO threadmarks
        (thread_id, post_id, label)
      VALUES
        (?, ?, ?)
      ON DUPLICATE KEY UPDATE
        label = values(label)
    ', array($thread_id, $post_id, $label));
    $rowsAffected = $stmt->rowCount();

    // http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html
    // 1 - new row, 2 - update
    if ($rowsAffected == 1)
    {
        $db->query('
          UPDATE xf_thread
          SET threadmark_count = threadmark_count + 1
          WHERE thread_id = ?
        ', $thread_id);
    }

    XenForo_Db::commit($db);

    return true;
  }

  public function deleteThreadMark($threadmark, $decrementCount = false)
  {
    $db = $this->_getDb();
    if (empty($threadmark['threadmark_id']))
    {
        XenForo_Error::debug("Require a threadmark_id in:".var_export($threadmark, true));
        return;
    }

    XenForo_Db::beginTransaction($db);

    $db->query('
        DELETE FROM threadmarks WHERE threadmark_id = ?
    ', $threadmark['threadmark_id']);

    if ($decrementCount && !empty($threadmark['thread_id']))
    {
        $this->modifyThreadMarkCount($threadmark['thread_id'], -1);
    }

    XenForo_Db::commit($db);

    return true;
  }

  public function modifyThreadMarkCount($thread_id, $increment)
  {
    $db = $this->_getDb();
    $db->query('
        UPDATE xf_thread
        SET threadmark_count = threadmark_count + ?
        WHERE thread_id = ? and threadmark_count + ? >= 0
    ', array($increment, $thread_id, $increment));
  }

  public function rebuildThreadMarkCache($thread_id)
  {
    $db = $this->_getDb();

    // remove orphaned threadmarks
    $db->query('
        DELETE `threadmarks`
        FROM `threadmarks`
        LEFT JOIN xf_post AS post on post.thread_id = threadmarks.thread_id and post.post_id = threadmarks.post_id
        where `threadmarks`.thread_id = ? and post.post_id is null;
    ', $thread_id);

    // recompute threadmark totals
    $db->query("
        UPDATE xf_thread
        SET threadmark_count = (SELECT count(threadmarks.threadmark_id)
                                FROM threadmarks
                                JOIN xf_post AS post ON post.post_id = threadmarks.post_id
                                where xf_thread.thread_id = threadmarks.thread_id and post.message_state = 'visible')
        WHERE thread_id = ?
    ", $thread_id);
  }

  public function getThreadIdsWithThreadMarks($limit =0, $offset = 0)
  {
    $db = $this->_getDb();
    return $db->fetchAll($this->limitQueryResults("
      SELECT distinct thread_id
      FROM threadmarks
      ORDER BY threadmarks.thread_id ASC
    ",$limit, $offset));
  }

  public function getByThreadId($threadId) {
    return $this->fetchAllKeyed("
      SELECT threadmarks.*
      FROM threadmarks
      JOIN xf_post AS post ON post.post_id = threadmarks.post_id
      WHERE threadmarks.thread_id = ? and post.message_state = 'visible'
      ORDER BY post.position ASC
    ", 'post_id', $threadId);
  }

  public function getByThreadIdAndPostId($threadId, $postId) {
    return $this->_getDb()->fetchRow("
      SELECT *
      FROM threadmarks
      WHERE thread_id = ?
        AND post_id = ?
    ", array($threadId, $postId));
  }

  public function getByThreadIdWithPostDate($threadId) {
    return $this->fetchAllKeyed("
      SELECT threadmarks.*, post.post_date
      FROM threadmarks
      JOIN xf_post AS post ON post.post_id = threadmarks.post_id
      WHERE threadmarks.thread_id = ? and post.message_state = 'visible'
      ORDER BY post.position ASC
    ", 'post_id', $threadId);
  }
}
