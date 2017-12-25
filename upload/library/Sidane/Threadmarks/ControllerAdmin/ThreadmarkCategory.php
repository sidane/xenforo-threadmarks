<?php

class Sidane_Threadmarks_ControllerAdmin_ThreadmarkCategory extends XenForo_ControllerAdmin_Abstract
{
  protected function _preDispatch($action)
  {
    $this->assertAdminPermission('sidane_threadmarks');
  }

  public function actionIndex()
  {
    $threadmarksModel = $this->_getThreadmarksModel();

    $threadmarkCategories = $threadmarksModel->getAllThreadmarkCategories();
    $threadmarkCategories = $threadmarksModel->prepareThreadmarkCategories(
      $threadmarkCategories
    );

    $viewParams = array(
      'threadmarkCategories'      => $threadmarkCategories,
      'totalThreadmarkCategories' => count($threadmarkCategories)
    );

    return $this->responseView(
      'Sidane_Threadmarks_ViewAdmin_ThreadmarkCategory_List',
      'sidane_threadmarks_category_list',
      $viewParams
    );
  }

  public function actionAdd()
  {
    $threadmarkCategory = array(
      'display_order'          => 0,
      'allowed_user_group_ids' => 2
    );

    /** @var XenForo_Model_UserGroup $userGoupModel */
    $userGoupModel = $this->getModelFromCache('XenForo_Model_UserGroup');

    $userGroups = $userGoupModel->getUserGroupOptions($threadmarkCategory['allowed_user_group_ids']);

    $viewParams = array(
      'threadmarkCategory' => $threadmarkCategory,
      'userGroups'         => $userGroups
    );

    return $this->responseView(
      'Sidane_Threadmarks_ViewAdmin_ThreadmarkCategory_Edit',
      'sidane_threadmarks_category_edit',
      $viewParams
    );
  }

  public function actionEdit()
  {
    $threadmarkCategoryId = $this->_input->filterSingle(
      'threadmark_category_id',
      XenForo_Input::UINT
    );
    $threadmarkCategory = $this->_getThreadmarkCategoryOrError(
      $threadmarkCategoryId
    );

    /** @var XenForo_Model_UserGroup $userGoupModel */
    $userGoupModel = $this->getModelFromCache('XenForo_Model_UserGroup');

    $userGroups = $userGoupModel->getUserGroupOptions($threadmarkCategory['allowed_user_group_ids']);

    $viewParams = array(
      'threadmarkCategory' => $threadmarkCategory,
      'userGroups'         => $userGroups
    );

    return $this->responseView(
      'Sidane_Threadmarks_ViewAdmin_ThreadmarkCategory_Edit',
      'sidane_threadmarks_category_edit',
      $viewParams
    );
  }

  public function actionSave()
  {
    $this->_assertPostOnly();

    $threadmarkCategoryId = $this->_input->filterSingle(
      'threadmark_category_id',
      XenForo_Input::UINT
    );

    $dwInput = $this->_input->filter(array(
      'threadmark_category_id' => XenForo_Input::STRING,
      'display_order'          => XenForo_Input::UINT,
      'allowed_user_group_ids' => array(
        XenForo_Input::UINT,
        'array' => true
      )
    ));

    $titlePhrase = $this->_input->filterSingle(
      'title',
      XenForo_Input::STRING
    );

    $dw = XenForo_DataWriter::create(
      'Sidane_Threadmarks_DataWriter_ThreadmarkCategory'
    );
    if ($threadmarkCategoryId)
    {
      $dw->setExistingData($threadmarkCategoryId);
    }
    $dw->bulkSet($dwInput);
    $dw->setExtraData(
      Sidane_Threadmarks_DataWriter_ThreadmarkCategory::DATA_TITLE,
      $titlePhrase
    );
    $dw->save();

    return $this->responseRedirect(
      XenForo_ControllerResponse_Redirect::SUCCESS,
      XenForo_Link::buildAdminLink('threadmark-categories').$this->getLastHash(
        $dw->get('threadmark_category_id')
      )
    );
  }

  public function actionDelete()
  {
    if ($this->isConfirmedPost())
    {
      return $this->_deleteData(
        'Sidane_Threadmarks_DataWriter_ThreadmarkCategory',
        'threadmark_category_id',
        XenForo_Link::buildAdminLink('threadmark-categories')
      );
    }

    $threadmarkCategoryId = $this->_input->filterSingle(
      'threadmark_category_id',
      XenForo_Input::UINT
    );
    $threadmarkCategory = $this->_getThreadmarkCategoryOrError(
      $threadmarkCategoryId
    );

    $threadmarkCategoryDw = XenForo_DataWriter::create(
      'Sidane_Threadmarks_DataWriter_ThreadmarkCategory'
    );
    $threadmarkCategoryDw->setExistingData($threadmarkCategory, true);
    $threadmarkCategoryDw->preDelete();

    if ($errors = $threadmarkCategoryDw->getErrors())
    {
      return $this->responseError($errors);
    }

    $viewParams = array(
      'threadmarkCategory' => $threadmarkCategory
    );

    return $this->responseView(
      'Sidane_Threadmarks_ViewAdmin_ThreadmarkCategory_Delete',
      'sidane_threadmarks_category_delete',
      $viewParams
    );
  }

  protected function _getThreadmarkCategoryOrError($threadmarkCategoryId)
  {
    $threadmarksModel = $this->_getThreadmarksModel();

    $threadmarkCategory = $threadmarksModel->getThreadmarkCategoryById(
      $threadmarkCategoryId
    );

    if (!$threadmarkCategory)
    {
      throw $this->responseException($this->responseError(
        new XenForo_Phrase('sidane_requested_threadmark_category_not_found'),
        404
      ));
    }

    $threadmarkCategory = $threadmarksModel->prepareThreadmarkCategory(
      $threadmarkCategory
    );

    return $threadmarkCategory;
  }

  /**
   * @return Sidane_Threadmarks_Model_Threadmarks|XenForo_Model
   */
  protected function _getThreadmarksModel()
  {
    return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
  }
}
