<?php

class Sidane_Threadmarks_DataWriter_Post extends XFCP_Sidane_Threadmarks_DataWriter_Post
{
  protected function _messagePostSave()
  {
    parent::_messagePostSave();

    if ($this->isUpdate() && $this->isChanged('message_state'))
    {
      $threadmark = $this->_getThreadMarkModel()->getByPostId($this->get('post_id'));
      if (!empty($threadmark))
      {
        $dw = XenForo_DataWriter::create("Sidane_Threadmarks_DataWriter_Threadmark");
        $dw->setExistingData($threadmark['threadmark_id']);
        $dw->set('message_state', $this->get('message_state'));
        $dw->save();
      }
    }
    else if ($this->isInsert() && Sidane_Threadmarks_Globals::$threadmarkLabel)
    {
      $post = $this->getMergedData();
      $thread = $this->getDiscussionData();
      $this->_getThreadMarkModel()->setThreadMark($thread, $post, Sidane_Threadmarks_Globals::$threadmarkLabel);
    }
  }

  protected function _messagePostDelete()
  {
    parent::_messagePostDelete();

    $threadmark = $this->_getThreadMarkModel()->getByPostId($this->get('post_id'));
    if (!empty($threadmark))
    {
      $dw = XenForo_DataWriter::create("Sidane_Threadmarks_DataWriter_Threadmark");
      $dw->setExistingData($threadmark['threadmark_id']);
      $dw->delete();
    }
  }

  protected function _getThreadMarkModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}