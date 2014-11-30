<?php

class Sidane_Threadmarks_Model_Threadmarks extends XenForo_Model
{

  public function getByThreadId($threadId) {
    return $this->fetchAllKeyed("
      SELECT *
      FROM threadmarks
      WHERE thread_id = ?
      ORDER BY post_id ASC
    ", 'post_id', $threadId);
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
