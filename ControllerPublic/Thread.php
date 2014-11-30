<?php

class Sidane_Threadmarks_ControllerPublic_Thread extends XFCP_Sidane_Threadmarks_ControllerPublic_Thread
{

  public function actionIndex()
  {
    $parent = parent::actionIndex();
    $thread = $parent->params['thread'];

    if ($thread['has_threadmarks']) {
      $threadmarksModel = $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
      $menuLimit = intval(XenForo_Application::get('options')->threadmarksLimit);

      $threadmarks = $threadmarksModel->getByThreadId($thread['thread_id']);
      $totalThreadmarks = count($threadmarks);

      if ($totalThreadmarks == 0) {
        return $parent;
      }

      if ($totalThreadmarks > $menuLimit) {
        $threadmarks = array_slice($threadmarks, $totalThreadmarks - $menuLimit, null, true);
        $parent->params['thread']['more_threadmarks'] = true;
      }

      $parent->params['thread']['threadmarks'] = $threadmarks;
      $parent->params['thread']['total_threadmarks'] = $totalThreadmarks;

      $parent->params['thread']['threadmarks_post_ids'] = array_map(function($threadmark) {
        return $threadmark['post_id'];
      }, $threadmarks);
    }

    return $parent;
  }

}
