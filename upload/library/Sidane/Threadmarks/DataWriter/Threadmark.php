<?php

class Sidane_Threadmarks_DataWriter_Threadmark extends XenForo_DataWriter
{

  protected function _getFields()
  {
    return array(
      'threadmarks' => array(
        'threadmark_id'          => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
        'user_id'                => array('type' => self::TYPE_UINT, 'required' => true),
        'post_date'              => array('type' => self::TYPE_UINT, 'required' => true, 'default' => XenForo_Application::$time),
        'thread_id'              => array('type' => self::TYPE_UINT, 'required' => true),
        'post_id'                => array('type' => self::TYPE_UINT, 'required' => true),
        'label'                  => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 75,
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

    parent::_postSave();
  }

  protected function _postDelete()
  {
    parent::_postDelete();

    $this->getModelFromCache('XenForo_Model_EditHistory')->deleteEditHistoryForContent(
      $this->getContentType(), $this->getContentId()
    );

    $this->_updateThreadMarkCount(true);
  }

  protected function getContentType()
  {
    return 'threadmark';
  }

  protected function getContentId()
  {
    return $this->get('post_id');
  }

  protected function _updateThreadMarkCount($isDelete = false)
  {
    if ($this->getExisting('message_state') == 'visible'
      && ($this->get('message_state') != 'visible' || $isDelete)
    )
    {
      $this->_db->query('
        UPDATE xf_thread
        SET threadmark_count = IF(threadmark_count > 0, threadmark_count - 1, 0)
        WHERE thread_id = ?
      ', $this->get('thread_id'));
    }
    else if ($this->get('message_state') == 'visible' && $this->getExisting('message_state') != 'visible')
    {
      $this->_db->query('
        UPDATE xf_thread
        SET threadmark_count = threadmark_count + 1
        WHERE thread_id = ?
      ', $this->get('thread_id'));
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

  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}