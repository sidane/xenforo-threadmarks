<?php

class Sidane_Threadmarks_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_Sidane_Threadmarks_XenForo_DataWriter_DiscussionMessage_Post
{
  const DATA_THREADMARK = 'threadmarkInfo';

  protected function _getThreadmarkData()
  {
    if (!$threadmark = $this->getExtraData(self::DATA_THREADMARK))
    {
      $threadmark = $this->_getThreadmarksModel()->getByPostId(
        $this->get('post_id')
      );
      $this->setExtraData(self::DATA_THREADMARK, $threadmark);
    }

    return $threadmark;
  }

  protected function _messagePreSave()
  {
    parent::_messagePreSave();
    if (
      $this->isInsert() &&
      !empty(Sidane_Threadmarks_Globals::$threadmarkLabel) &&
      !empty(Sidane_Threadmarks_Globals::$threadmarkCategoryId)
    )
    {
      $post = $this->getMergedData();
      $thread = $this->getDiscussionData();
      $forum = $this->_getForumInfo();

      $threadmarksModel = $this->_getThreadmarksModel();

      $canAddThreadmark = $threadmarksModel->canAddThreadmark(
        $post,
        $thread,
        $forum,
        $null
      );
      if (!$canAddThreadmark)
      {
        $this->error(new XenForo_Phrase(
          'you_do_not_have_permission_to_add_threadmarks'
        ));
      }

      $threadmarkCategory = $threadmarksModel->getThreadmarkCategoryById(
        Sidane_Threadmarks_Globals::$threadmarkCategoryId
      );
      if (!$threadmarksModel->canUseThreadmarkCategory($threadmarkCategory))
      {
        $this->error(new XenForo_Phrase(
          'you_do_not_have_permission_to_use_threadmark_category'
        ));
      }
    }
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
        $dw = XenForo_DataWriter::create(
          'Sidane_Threadmarks_DataWriter_Threadmark'
        );
        $dw->setExistingData($threadmark['threadmark_id']);
        $dw->setExtraData(
          Sidane_Threadmarks_DataWriter_Threadmark::DATA_THREAD,
          $thread
        );
        $dw->set('message_state', $this->get('message_state'));
        $dw->save();
      }
    } else if (
      $this->isInsert() &&
      Sidane_Threadmarks_Globals::$threadmarkLabel &&
      Sidane_Threadmarks_Globals::$threadmarkCategoryId
    )
    {
      $post = $this->getMergedData();
      $thread = $this->getDiscussionData();
      $this->_getThreadmarksModel()->setThreadMark(
        $thread,
        $post,
        Sidane_Threadmarks_Globals::$threadmarkLabel,
        Sidane_Threadmarks_Globals::$threadmarkCategoryId
      );
    }
  }

  protected function _messagePostDelete()
  {
    parent::_messagePostDelete();

    $threadmark = $this->_getThreadmarkData();
    if (!empty($threadmark))
    {
      $thread = $this->getDiscussionData();
      $dw = XenForo_DataWriter::create(
        'Sidane_Threadmarks_DataWriter_Threadmark'
      );
      $dw->setExistingData($threadmark['threadmark_id']);
      $dw->setExtraData(
        Sidane_Threadmarks_DataWriter_Threadmark::DATA_THREAD,
        $thread
      );
      $dw->delete();
    }
  }

  /**
   * @return XenForo_Model|Sidane_Threadmarks_Model_Threadmarks
   */
  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}

if (false)
{
  class XFCP_Sidane_Threadmarks_XenForo_DataWriter_DiscussionMessage_Post extends XenForo_DataWriter_DiscussionMessage_Post {}
}
