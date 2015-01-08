<?php

class Sidane_Threadmarks_Model_Threadmarks extends XenForo_Model
{

  public function SetThreadMark($thread_id, $post_id, $label) {
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
        ', $thread['thread_id']);
    }
    
    XenForo_Db::commit($db);

    return true;
  }

  public function deleteThreadMark($threadmark) {
    $db = $this->_getDb();

    XenForo_Db::beginTransaction($db);

    $db->query('
      DELETE FROM threadmarks WHERE threadmark_id = ?
    ', $threadmark['threadmark_id']);


    $db->query('
      UPDATE xf_thread
      SET threadmark_count = threadmark_count - 1
      WHERE thread_id = ? and threadmark_count > 0
    ', $threadmark['thread_id']);
    
    XenForo_Db::commit($db);

    return true;
  }

  public function getByThreadId($threadId) {
    return $this->fetchAllKeyed("
      SELECT *
      FROM threadmarks
      WHERE thread_id = ?
      ORDER BY post_id ASC
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
      SELECT threadmarks.*, xf_post.post_date
      FROM threadmarks
      INNER JOIN xf_post ON (threadmarks.post_id = xf_post.post_id)
      WHERE threadmarks.thread_id = ?
      ORDER BY post_id ASC
    ", 'post_id', $threadId);
  }
}
