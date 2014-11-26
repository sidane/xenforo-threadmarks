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

}
