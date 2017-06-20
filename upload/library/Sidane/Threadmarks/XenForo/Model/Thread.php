<?php

class Sidane_Threadmarks_XenForo_Model_Thread extends XFCP_Sidane_Threadmarks_XenForo_Model_Thread
{
  public function prepareThread(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
  {
    $thread = parent::prepareThread($thread, $forum, $nodePermissions, $viewingUser);

    if (isset($thread['threadmark_category_data']))
    {
      $thread['threadmark_category_data'] = $this
        ->_getThreadmarksModel()
        ->decodeThreadmarkCategoryData($thread['threadmark_category_data']);
    }

    return $thread;
  }

  /**
   * @return Sidane_Threadmarks_Model_Threadmarks
   */
  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_Sidane_Threadmarks_XenForo_Model_Thread extends XenForo_Model_Thread {}
}
