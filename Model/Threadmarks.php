<?php

class Sidane_Threadmarks_Model_Threadmarks extends XenForo_Model
{

  public function createThreadMark($thread, $post, $label) {
    $db = $this->_getDb();

    XenForo_Db::beginTransaction($db);

    $db->query('
      INSERT IGNORE INTO threadmarks
        (thread_id, post_id, label)
      VALUES
        (?, ?, ?)
    ', array($thread['thread_id'], $post['post_id'], $label));

    $db->query('
      UPDATE xf_thread
      SET has_threadmarks = 1
      WHERE thread_id = ?
    ', $thread['thread_id']);

    XenForo_Db::commit($db);

    return true;
  }

  public function deleteThreadMark($threadmark) {
    $db = $this->_getDb();

    XenForo_Db::beginTransaction($db);

    $db->query('
      DELETE FROM threadmarks WHERE threadmark_id = ?
    ', $threadmark['threadmark_id']);

    $threadmarks = $this->getByThreadId($threadmark['thread_id']);

    if (count($threadmarks) == 0) {
      $db->query('
        UPDATE xf_thread
        SET has_threadmarks = 0
        WHERE thread_id = ?
      ', $threadmark['thread_id']);
    }

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

  public function updateThreadmark($threadmark, $newLabel) {
    $db = $this->_getDb();

    $db->query('
      UPDATE threadmarks
        SET label = ?
      WHERE thread_id = ?
        AND post_id = ?
    ', array($newLabel, $threadmark['thread_id'], $threadmark['post_id']));

    return true;
  }

}
