<?php

class Sidane_Threadmarks_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_ControllerPublic_Thread
{

  public function actionIndex()
  {
    $parent = parent::actionIndex();
    $thread = $parent->params['thread'];

    if ($thread['has_threadmarks']) {
      $threadmarksModel = $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
      $threadmarks = $threadmarksModel->getByThreadId($thread['thread_id']);
      $parent->params['thread']['threadmarks'] = $threadmarks;
    }

    return $parent;
  }

}
