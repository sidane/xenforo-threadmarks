<?php

class Sidane_Threadmarks_DataWriter_ThreadmarkCategory extends XenForo_DataWriter
{
  const DATA_TITLE = 'phraseTitle';

  protected function _getFields()
  {
    return array(
      'threadmark_category' => array(
        'threadmark_category_id' => array(
          'type'          => self::TYPE_UINT,
          'autoIncrement' => true
        ),
        'display_order' => array(
          'type' => self::TYPE_UINT
        ),
        'allowed_user_group_ids' => array(
          'type'         => self::TYPE_UNKNOWN,
          'default'      => '2',
          'verification' => array(
            'XenForo_DataWriter_Helper_User',
            'verifyExtraUserGroupIds'
          )
        )
      )
    );
  }

  protected function _getExistingData($data)
  {
    if (!$threadmarkCategoryId = $this->_getExistingPrimaryKey(
      $data,
      'threadmark_category_id'
    ))
    {
      return false;
    }

    $threadmarkCategory = $this->_getThreadmarksModel()->getThreadmarkCategoryById(
      $threadmarkCategoryId
    );

    return array('threadmark_category' => $threadmarkCategory);
  }

  protected function _getUpdateCondition($tableName)
  {
    $threadmarkCategoryId = $this->_db->quote($this->getExisting(
      'threadmark_category_id'
    ));

    return "threadmark_category_id = {$threadmarkCategoryId}";
  }

  protected function _preSave()
  {
    $titlePhrase = $this->getExtraData(self::DATA_TITLE);
    if ($titlePhrase !== null && strlen($titlePhrase) == 0)
    {
      $this->error(new XenForo_Phrase('please_enter_valid_title'), 'title');
    }
  }

  protected function _postSave()
  {
    $titlePhrase = $this->getExtraData(self::DATA_TITLE);
    if ($titlePhrase !== null) {
      $this->_insertOrUpdateMasterPhrase(
        $this->_getTitlePhraseName($this->get('threadmark_category_id')),
        $titlePhrase,
        ''
      );
    }
  }

  protected function _preDelete()
  {
    if ($this->get('threadmark_category_id') === 1)
    {
      $this->error(new XenForo_Phrase(
        'sidane_you_may_not_delete_default_threadmark_category'
      ));
    }
  }

  protected function _postDelete()
  {
    $threadmarkCategoryId = $this->get('threadmark_category_id');

    $this->_deleteMasterPhrase($this->_getTitlePhraseName($threadmarkCategoryId));

    $children = $this
      ->_getThreadmarksModel()
      ->getThreadmarksByCategory($threadmarkCategoryId);

    foreach ($children as $child)
    {
      $dw = XenForo_DataWriter::create('Sidane_Threadmarks_DataWriter_Threadmark');
      $dw->setExistingData($child);
      $dw->delete();
    }
  }

  protected function _getTitlePhraseName($threadmarkCategoryId)
  {
    return $this->_getThreadmarksModel()->getThreadmarkCategoryTitlePhraseName(
      $threadmarkCategoryId
    );
  }

  /**
   * @return Sidane_Threadmarks_Model_Threadmarks
   */
  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
