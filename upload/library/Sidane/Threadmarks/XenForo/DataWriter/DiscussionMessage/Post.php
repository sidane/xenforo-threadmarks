<?php

class Sidane_Threadmarks_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_Sidane_Threadmarks_XenForo_DataWriter_DiscussionMessage_Post
{
  const DATA_THREADMARK = 'threadmarkInfo';

  protected function _getThreadmarkData()
  {
    if (!$threadmark = $this->getExtraData(self::DATA_THREADMARK))
    {
      $threadmark = $this->_getThreadMarkModel()->getByPostId($this->get('post_id'));
      $this->setExtraData(self::DATA_THREADMARK, $threadmark);
    }

    return $threadmark;
  }

  protected function _messagePostSave()
  {
    parent::_messagePostSave();

    if ($this->isUpdate() && $this->isChanged('message_state'))
    {
      $threadmark = $this->_getThreadmarkData();
      if (!empty($threadmark))
      {
        $thread = $this->getDiscussionData();
        $dw = XenForo_DataWriter::create("Sidane_Threadmarks_DataWriter_Threadmark");
        $dw->setExistingData($threadmark['threadmark_id']);
        $dw->setExtraData(Sidane_Threadmarks_DataWriter_Threadmark::DATA_THREAD, $thread);
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

    $threadmark = $this->_getThreadmarkData();
    if (!empty($threadmark))
    {
      $dw = XenForo_DataWriter::create("Sidane_Threadmarks_DataWriter_Threadmark");
      $dw->setExistingData($threadmark['threadmark_id']);
      $dw->setExtraData(Sidane_Threadmarks_DataWriter_Threadmark::DATA_THREAD, $thread);
      $dw->delete();
    }
  }

  protected function _getThreadMarkModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}