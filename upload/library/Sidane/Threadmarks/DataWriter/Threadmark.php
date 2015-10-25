<?php

class Sidane_Threadmarks_DataWriter_Threadmark extends XenForo_DataWriter
{

  protected function _getFields()
  {
    return array(
      'threadmarks' => array(
        'threadmark_id'          => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
        'user_id'                => array('type' => self::TYPE_UINT, 'required' => true),
        'threadmark_date'        => array('type' => self::TYPE_UINT, 'required' => true, 'default' => XenForo_Application::$time),
        'thread_id'              => array('type' => self::TYPE_UINT, 'required' => true),
        'post_id'                => array('type' => self::TYPE_UINT, 'required' => true),
        'label'                  => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 255,
             'requiredError' => 'please_enter_label_for_threadmark'
        ),
        'message_state'          => array('type' => self::TYPE_STRING, 'default' => 'visible',
          'allowedValues' => array('visible', 'moderated', 'deleted')
        ),
        'last_edit_date'         => array('type' => self::TYPE_UINT, 'default' => 0),
        'last_edit_user_id'      => array('type' => self::TYPE_UINT, 'default' => 0),
        'edit_count'             => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
        'position'               => array('type' => self::TYPE_UINT_FORCED),
      )
    );
  }

  protected function _getExistingData($data)
  {
    if (!$threadmark_id = $this->_getExistingPrimaryKey($data, 'threadmark_id'))
    {
      return false;
    }

    return array('threadmarks' => $this->_getThreadmarksModel()->getThreadMarkById($threadmark_id));
  }

  protected function _getUpdateCondition($tableName)
  {
    return 'threadmark_id = ' . $this->_db->quote($this->getExisting('threadmark_id'));
  }

  protected function _preSave()
  {
    parent::_preSave();

    if ($this->isUpdate() && $this->isChanged('label'))
    {
      $this->set('last_edit_date', XenForo_Application::$time);
      $this->set('last_edit_user_id', XenForo_Visitor::getUserId());
      $this->set('edit_count', $this->get('edit_count') + 1);
    }
  }

  protected function _postSave()
  {
    if ($this->isUpdate())
    {
      if ($this->isChanged('label'))
      {
        $this->_insertEditHistory();
      }
    }
    else if ($this->isInsert())
    {
    }

    if($this->isChanged('message_state'))
    {
      $this->_updateThreadMarkCount();
    }

    $this->_indexForSearch();
    $this->_publishAndNotify();

    parent::_postSave();
  }

  protected function _postDelete()
  {
    parent::_postDelete();

    $this->getModelFromCache('XenForo_Model_EditHistory')->deleteEditHistoryForContent(
      $this->getContentType(), $this->getContentId()
    );

    $this->_updateThreadMarkCount(true);
    $this->_deleteFromSearchIndex();
    $this->_deleteFromNewsFeed();
  }

  protected function getContentType()
  {
    return 'threadmark';
  }

  protected function getContentId()
  {
    return $this->get('post_id');
  }

  protected function _indexForSearch()
  {
    if ($this->get('message_state') == 'visible')
    {
      if ($this->getExisting('message_state') != 'visible' || $this->isChanged('message'))
      {
        $this->_insertOrUpdateSearchIndex();
      }
    }
    else if ($this->isUpdate() && $this->get('message_state') != 'visible' && $this->getExisting('message_state') == 'visible')
    {
      $this->_deleteFromSearchIndex();
    }
  }

  protected function _insertOrUpdateSearchIndex()
  {
    $dataHandler = $this->_getSearchDataHandler();
    if (!$dataHandler)
    {
      return;
    }

    $thread = $this->_getThreadModel()->getThreadById($this->get('thread_id'));

    $indexer = new XenForo_Search_Indexer();
    $dataHandler->insertIntoIndex($indexer, $this->getMergedData(), $thread);
  }

  protected function _deleteFromSearchIndex()
  {
    $dataHandler = $this->_getSearchDataHandler();
    if (!$dataHandler)
    {
      return;
    }

    $indexer = new XenForo_Search_Indexer();
    $dataHandler->deleteFromIndex($indexer, $this->getMergedData());
  }

  protected function _publishAndNotify()
  {
    if ($this->isInsert())
    {
      $this->_publishToNewsFeed();
    }
  }

  protected function _publishToNewsFeed()
  {
    $this->_getNewsFeedModel()->publish(
      $this->get('user_id'),
      $this->get('username'),
      $this->getContentType(),
      $this->getContentId(),
      ($this->isUpdate() ? 'update' : 'insert')
    );
  }

  protected function _deleteFromNewsFeed()
  {
    $this->_getNewsFeedModel()->delete($this->getContentType(), $this->getContentId() );
  }

  protected function _updateThreadMarkCount($isDelete = false)
  {
    if ($this->getExisting('message_state') == 'visible'
      && ($this->get('message_state') != 'visible' || $isDelete)
    )
    {
      $this->_db->query("
        UPDATE threadmarks
        SET position = position - 1
        WHERE thread_id = ? and position >= ? and post_id <> ? and threadmarks.message_state = 'visible'
      ", array($this->get('thread_id'), $this->get('position'), $this->get('post_id')));

      $this->_db->query("
        UPDATE xf_thread
        SET threadmark_count = IF(threadmark_count > 0, threadmark_count - 1, 0)
           ,firstThreadmarkId = COALESCE((SELECT min(position) FROM threadmarks WHERE threadmarks.thread_id = xf_thread.thread_id and threadmarks.message_state = 'visible'), 0 )
           ,lastThreadmarkId = COALESCE((SELECT max(position) FROM threadmarks WHERE threadmarks.thread_id = xf_thread.thread_id and threadmarks.message_state = 'visible'), 0 )
        WHERE thread_id = ?
      ", $this->get('thread_id'));
    }
    else if ($this->get('message_state') == 'visible' && $this->getExisting('message_state') != 'visible')
    {
      $this->_db->query("
        UPDATE threadmarks
        SET position = position + 1
        WHERE thread_id = ? and position >= ? and post_id <> ? and threadmarks.message_state = 'visible'
      ", array($this->get('thread_id'), $this->get('position'), $this->get('post_id')));

      $this->_db->query("
        UPDATE xf_thread
        SET threadmark_count = threadmark_count + 1
           ,firstThreadmarkId = COALESCE((SELECT min(position) FROM threadmarks WHERE threadmarks.thread_id = xf_thread.thread_id and threadmarks.message_state = 'visible'), 0 )
           ,lastThreadmarkId = COALESCE((SELECT max(position) FROM threadmarks WHERE threadmarks.thread_id = xf_thread.thread_id and threadmarks.message_state = 'visible'), 0 )
        WHERE thread_id = ?
      ", $this->get('thread_id'));
    }
  }

  protected function _insertEditHistory()
  {
    $historyDw = XenForo_DataWriter::create('XenForo_DataWriter_EditHistory', XenForo_DataWriter::ERROR_SILENT);
    $historyDw->bulkSet(array(
        'content_type' => $this->getContentType(),
        'content_id' => $this->getContentId(),
        'edit_user_id' => XenForo_Visitor::getUserId(),
        'old_text' => $this->getExisting('label')
    ));
    $historyDw->save();
  }

  protected function _getSearchDataHandler()
  {
    return XenForo_Search_DataHandler_Abstract::create('Sidane_Threadmarks_Search_DataHandler_Threadmark');
  }

  protected function _getThreadModel()
  {
    return $this->getModelFromCache('XenForo_Model_Thread');
  }

  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }

  protected function _getNewsFeedModel()
  {
    return $this->getModelFromCache('XenForo_Model_NewsFeed');
  }
}