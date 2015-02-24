<?php

class Sidane_Threadmarks_EditHistoryHandler_Threadmark extends XenForo_EditHistoryHandler_Abstract
{
    protected $_prefix = 'posts';

    protected function _getContent($contentId, array $viewingUser)
    {
      /* @var $postModel XenForo_Model_Post */
      $postModel = XenForo_Model::create('XenForo_Model_Post');
      $post = $postModel->getPostById($contentId, array(
        'join' => XenForo_Model_Post::FETCH_FORUM | XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_USER |  Sidane_Threadmarks_Model_Post::FETCH_THREADMARKS_FULL,
        'permissionCombinationId' => $viewingUser['permission_combination_id']
      ));
      if ($post)
      {
        $post['permissions'] = XenForo_Permission::unserializePermissions($post['node_permission_cache']);
        $prefix = 'threadmark';
        $remap = array('label', 'edit_count', 'user_id', 'username', 'last_edit_date', 'last_edit_user_id');
        foreach($remap as $remapItem)
        {
          $key = $prefix .'_'. $remapItem;
          if (isset($post[$key]))
          {
            $post[$remapItem] = $post[$key];
            unset($post[$key]);
          }
        }
      }      
      return $post;
    }

    protected function _canViewHistoryAndContent(array $content, array $viewingUser)
    {
      $threadmarkModel = XenForo_Model::create('Sidane_Threadmarks_Model_Threadmarks');
      return $threadmarkModel->canViewThreadmark($content, $null, $content['permissions'], $viewingUser);
    }

    protected function _canRevertContent(array $content, array $viewingUser)
    {
      $threadmarkModel = XenForo_Model::create('Sidane_Threadmarks_Model_Threadmarks');
      return $threadmarkModel->canEditThreadmark($content, $content, $content, $null, $content['permissions'], $viewingUser);
    }

    public function getText(array $content)
    {
      return htmlspecialchars($content['label']);
    }

    public function getTitle(array $content)
    {
      //return new XenForo_Phrase('post_in_thread_x', array('label' => $content['label']));
      return htmlspecialchars($content['label']); // TODO
    }

    public function getBreadcrumbs(array $content)
    {
      /* @var $nodeModel XenForo_Model_Node */
      $nodeModel = XenForo_Model::create('XenForo_Model_Node');

      $node = $nodeModel->getNodeById($content['node_id']);
      if ($node)
      {
        $crumb = $nodeModel->getNodeBreadCrumbs($node);
        $crumb[] = array(
          'href' => XenForo_Link::buildPublicLink('full:threads', $content),
          'value' => $content['label']
        );
        return $crumb;
      }
      else
      {
        return array();
      }
    }

    public function getNavigationTab()
    {
      return 'forums';
    }

    public function formatHistory($string, XenForo_View $view)
    {
      return htmlspecialchars($string);
    }

    public function revertToVersion(array $content, $revertCount, array $history, array $previous = null)
    {
      $dw = XenForo_DataWriter::create('Sidane_Threadmarks_DataWriter_Threadmark' , XenForo_DataWriter::ERROR_SILENT);
      $dw->setExistingData($content['threadmark_id']);
      $dw->set('label', $history['old_text']);
      $dw->set('edit_count', $dw->get('edit_count') + 1);
      if ($dw->get('edit_count'))
      {
        if (!$previous || $previous['edit_user_id'] != $content['user_id'])
        {
          // if previous is a mod edit, don't show as it may have been hidden
          $dw->set('last_edit_date', 0);
        }
        else if ($previous && $previous['edit_user_id'] == $content['user_id'])
        {
          $dw->set('last_edit_date', $previous['edit_date']);
          $dw->set('last_edit_user_id', $previous['edit_user_id']);
        }
      }

      return $dw->save();
    }
}