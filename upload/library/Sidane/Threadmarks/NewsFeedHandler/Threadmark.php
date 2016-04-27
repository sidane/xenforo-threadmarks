<?php

class Sidane_Threadmarks_NewsFeedHandler_Threadmark extends XenForo_NewsFeedHandler_Abstract
{
  protected $_threadmarkModel = null;
  protected $_postModel = null;
  protected $_threadModel = null;
  
  public function getContentByIds(array $contentIds, $model, array $viewingUser)
  {
    $postModel = $this->_getPostModel();

    $posts = $postModel->getPostsByIds($contentIds, array(
      'join' => XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_FORUM | XenForo_Model_Post::FETCH_USER | Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS_FULL,
      'permissionCombinationId' => $viewingUser['permission_combination_id']
    ));

    $posts = $postModel->unserializePermissionsInList($posts, 'node_permission_cache');

    return $posts;
  }

  public function canViewNewsFeedItem(array $item, $content, array $viewingUser)
  {
    return $this->_getPostModel()->canViewPostAndContainer(
      $content, $content, $content, $null, $content['permissions'], $viewingUser
    ) && $this->_getThreadmarkModel()->canViewThreadmark($content, $null, $content['permissions'], $viewingUser);
  }

	public function prepareNewsFeedItem(array $item, array $viewingUser)
	{
    $post = $item['content'];

    $post = $this->_getPostModel()->preparePost($post, $post, $post, $post['permissions'], $viewingUser);
    $post['title'] = XenForo_Helper_String::censorString($post['title']);
    unset($post['message']);
    unset($post['message_parsed']);

    $item['content'] = $post;
    return parent::prepareNewsFeedItem($item, $viewingUser);
	}
  
  protected function _getThreadmarkModel()
  {
    if (!$this->_threadmarkModel)
    {
      $this->_threadmarkModel = XenForo_Model::create('Sidane_Threadmarks_Model_Threadmarks');
    }

    return $this->_threadmarkModel;
  }
  
  protected function _getPostModel()
  {
    if (!$this->_postModel)
    {
      $this->_postModel = XenForo_Model::create('XenForo_Model_Post');
    }

    return $this->_postModel;
  }

  protected function _getThreadModel()
  {
    if (!$this->_threadModel)
    {
      $this->_threadModel = XenForo_Model::create('XenForo_Model_Thread');
    }

    return $this->_threadModel;
  }
}