<?php

class Sidane_Threadmarks_Search_DataHandler_Threadmark extends XenForo_Search_DataHandler_Abstract
{
  protected $_threadmarkModel = null;
  protected $_postModel = null;
  protected $_threadModel = null;
  protected $hasElasticEss = false;

  public function __construct()
  {
    // use the proxy class existence as a cheap check for if this addon is enabled.
    if (XenForo_Application::getOptions()->enableElasticsearch && class_exists('XenES_Model_Elasticsearch', true))
    {
      XenForo_Model::create('XenES_Model_Elasticsearch');
      $this->hasElasticEss = class_exists('XFCP_SV_ElasticEss_XenES_Model_Elasticsearch', false);
    }
  }


  /**
   * Inserts a new record or replaces an existing record in the index.
   *
   * @param XenForo_Search_Indexer $indexer Object that will will manipulate the index
   * @param array $data Data that needs to be updated
   * @param array|null $parentData Data about the parent info (eg, for a post, the parent thread)
   */
  protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
  {
    if ($data['message_state'] != 'visible')
    {
      return;
    }

    $metadata = array();
    $title = '';

    if ($parentData)
    {
      $thread = $parentData;
      if ($thread['discussion_state'] != 'visible')
      {
        return;
      }

      $metadata['node'] = $thread['node_id'];
    }

    $metadata['thread'] = $data['thread_id'];

    $indexer->insertIntoIndex(
      'threadmark', $data['post_id'],
      $title, $data['label'],
      $data['threadmark_date'], $data['user_id'], $data['thread_id'], $metadata
    );
  }

  /**
   * Updates a record in the index.
   *
   * @param XenForo_Search_Indexer $indexer Object that will will manipulate the index
   * @param array $data Data that needs to be updated
   * @param array $fieldUpdates Key-value fields to update
   */
  protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
  {
    $indexer->updateIndex('threadmark', $data['post_id'], $fieldUpdates);
  }

  /**
   * Deletes one or more records from the index. Wrapper around {@link _deleteFromIndex()}.
   *
   * @param XenForo_Search_Indexer $indexer Object that will will manipulate the index
   * @param array $dataList A list of data to remove. Each element is an array of the data from one record or an ID.
   */
  protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
  {
    $postIds = array();
    foreach ($dataList AS $data)
    {
      $postIds[] = is_array($data) ? $data['post_id'] : $data;
    }

    $indexer->deleteFromIndex('threadmark', $postIds);
  }

  /**
   * Rebuilds the index in bulk.
   *
   * @param XenForo_Search_Indexer $indexer Object that will will manipulate the index
   * @param integer $lastId The last ID that was processed. Should continue with the IDs above this.
   * @param integer $batchSize Number of records to process at once
   *
   * @return integer|false The last ID that was processed or false if none were processed
   */
  public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
  {
    $postIds = $this->_getThreadMarkModel()->getThreadMarkIdsInRange($lastId, $batchSize);
    if (!$postIds)
    {
      return false;
    }

    $this->quickIndex($indexer, $postIds);

    return max($postIds);
  }

  /**
   * Indexes the specified content IDs.
   *
   * @param XenForo_Search_Indexer $indexer
   * @param array $contentIds
   *
   * @return array List of content IDs indexed
   */
  public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
  {
    $threadmarks = $this->_getThreadMarkModel()->getThreadMarkByIds($contentIds);
    if (empty($threadmarks))
    {
      return true;
    }

    $threadIds = array();
    foreach ($threadmarks AS $threadmark)
    {

      $threadIds[] = $threadmark['thread_id'];
    }

    $threads = $this->_getThreadModel()->getThreadsByIds(array_unique($threadIds));

    foreach ($threadmarks AS $threadmark)
    {
      $thread = (isset($threads[$threadmark['thread_id']]) ? $threads[$threadmark['thread_id']] : null);
      if (!$thread)
      {
        continue;
      }

      $this->insertIntoIndex($indexer, $threadmark, $thread);
    }

    return true;
  }

  public function getInlineModConfiguration()
  {
    return array(
      'name' => new XenForo_Phrase('post'),
      'route' => 'inline-mod/post/switch',
      'cookie' => 'posts',
      'template' => 'inline_mod_controls_posts'
    );
  }

  /**
   * Gets the additional, type-specific data for a list of results. If any of
   * the given IDs are not returned from this, they will be removed from the results.
   *
   * @param array $ids List of IDs of this content type.
   * @param array $viewingUser Information about the viewing user (keys: user_id, permission_combination_id, permissions)
   * @param array $resultsGrouped List of all results grouped by content type
   *
   * @return array Format: [id] => data, IDs not returned will be removed from results
   */
  public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
  {
    $postModel = $this->_getPostModel();

    $posts = $postModel->getPostsByIds($ids, array(
      'includeThreadmark' => true,
      'join' => XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_FORUM | XenForo_Model_Post::FETCH_USER,
      'permissionCombinationId' => $viewingUser['permission_combination_id']
    ));

    $posts = $postModel->unserializePermissionsInList($posts, 'node_permission_cache');
    foreach ($posts AS $postId => $post)
    {
      if ($post['post_id'] == $post['first_post_id'] && isset($resultsGrouped['thread'][$post['thread_id']]))
      {
        // matched first post and thread, skip the post
        unset($posts[$postId]);
      }
      if (empty($post['threadmark_id']))
      {
        // no threadmark is actually on this post
        unset($posts[$postId]);
      }
    }

    return $posts;
  }

  /**
   * Determines if the specific search result (data from getDataForResults()) can be viewed
   * by the given user. The user and combination ID will be the same as given to getDataForResults().
   *
   * @param array $result Data for a result
   * @param array $viewingUser Information about the viewing user (keys: user_id, permission_combination_id, permissions)
   *
   * @return boolean
   */
  public function canViewResult(array $result, array $viewingUser)
  {
    return $this->_getPostModel()->canViewPostAndContainer(
      $result, $result, $result, $null, $result['permissions'], $viewingUser
    ) && $this->_getThreadmarkModel()->canViewThreadmark($result, $result, $null, $result['permissions'], $viewingUser);
  }

  /**
  * Prepares a result for display.
  *
  * @see XenForo_Search_DataHandler_Abstract::prepareResult()
  */
  public function prepareResult(array $result, array $viewingUser)
  {
    $result = $this->_getPostModel()->preparePost($result, $result, $result, $result['permissions'], $viewingUser);
    $result['title'] = XenForo_Helper_String::censorString($result['title']);
    unset($result['message']);
    unset($result['message_parsed']);

    return $result;
  }

  public function addInlineModOption(array &$result)
  {
    return array();
    //return $this->_getPostModel()->addInlineModOptionToPost($result, $result, $result, $result['permissions']);
  }

  /**
   * Gets the date of the result (from the result's content).
   *
   * @param array $result
   *
   * @return integer
   */
  public function getResultDate(array $result)
  {
    // getResultDate can be called on data which hasn't been prepared for display
    if (isset($result['threadmark_threadmark_date']))
    {
      return $result['threadmark_threadmark_date'];
    }
    return $result['threadmark']['threadmark_date'];
  }

  /**
   * Render a result (as HTML).
   *
   * @param XenForo_View $view
   * @param array $result Data from result
   * @param array $search The search that was performed
   *
   * @return XenForo_Template_Abstract|string
   */
  public function renderResult(XenForo_View $view, array $result, array $search)
  {
    return $view->createTemplateObject('search_result_threadmark', array(
      'threadmark' => $result['threadmark'],
      'post' => $result,
      'thread' => $result,
      'forum' => array(
        'node_id' => $result['node_id'],
        'title' => $result['node_title'],
        'node_name' => $result['node_name']
      ),
      'search' => $search,
      'enableInlineMod' => $this->_inlineModEnabled
    ));
  }

  /**
   * Get the content types that will be searched, when doing a type-specific search for this type.
   * This may be multiple types (for example, thread and post for post searches).
   *
   * @return array
   */
  public function getSearchContentTypes()
  {
    return array('threadmark');
  }

  /**
   * Get type-specific constraints from input.
   *
   * @param XenForo_Input $input
   *
   * @return array
   */
  public function getTypeConstraintsFromInput(XenForo_Input $input)
  {
    $constraints = array();

    $replyCount = $input->filterSingle('reply_count', XenForo_Input::UINT);
    if ($replyCount)
    {
      $constraints['reply_count'] = $replyCount;
    }

    $threadmarkCount = $input->filterSingle('threadmark_count', XenForo_Input::UINT);
    if ($threadmarkCount)
    {
      $constraints['threadmark_count'] = $threadmarkCount;
    }

    $prefixes = $input->filterSingle('prefixes', XenForo_Input::UINT, array('array' => true));
    if ($prefixes && reset($prefixes))
    {
      $prefixes = array_unique($prefixes);
      $constraints['prefix'] = implode(' ', $prefixes);
      if (!$constraints['prefix'])
      {
        unset($constraints['prefix']); // just 0
      }
    }

    $threadId = $input->filterSingle('thread_id', XenForo_Input::UINT);
    if ($threadId)
    {
      $constraints['thread'] = $threadId;

      // undo things that don't make sense with this
      $constraints['titles_only'] = false;
    }

    return $constraints;
  }

  public function filterConstraints(XenForo_Search_SourceHandler_Abstract $sourceHandler, array $constraints)
  {
    $constraints = parent::filterConstraints($sourceHandler, $constraints);
    if (!isset($constraints['thread']) && !isset($constraints['node']))
    {
      $constraints['forum'] = 'all';
    }
    return $constraints;
  }

  /**
   * Process a type-specific constraint.
   *
   * @see XenForo_Search_DataHandler_Abstract::processConstraint()
   */
  public function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint, $constraintInfo, array $constraints)
  {
    switch ($constraint)
    {
      case 'forum':
        if ($constraintInfo == 'all')
        {
          if (!$this->hasElasticEss)
          {
            return false;
          }
          if (SV_ElasticEss_Globals::$allVisibleNodes === null)
          {
            $nodeModel = XenForo_Model::create('XenForo_Model_Node');
            $nodeList = $nodeModel->getViewableNodeList(null, true);
            $nodeList = $nodeModel->filterOrphanNodes($nodeList);
            SV_ElasticEss_Globals::$allVisibleNodes = array_keys($nodeList);
          }
          $nodes = SV_ElasticEss_Globals::$allVisibleNodes;
        }
        else
        {
            $nodes = preg_split('/\D/', strval($constraintInfo));
        }
        return array(
            'metadata' => array('node', array_map('intval', $nodes)),
        );
      case 'threadmark_count':
        $threadmarkCount = intval($constraintInfo);
        if ($threadmarkCount > 0)
        {
          return array(
            'query' => array('thread', 'threadmark_count', '>=', $threadmarkCount)
          );
        }
        break;

      case 'reply_count':
        $replyCount = intval($constraintInfo);
        if ($replyCount > 0)
        {
          return array(
            'query' => array('thread', 'reply_count', '>=', $replyCount)
          );
        }
        break;

      case 'prefix':
        if ($constraintInfo)
        {
          return array(
            'metadata' => array('prefix', preg_split('/\D+/', strval($constraintInfo))),
          );
        }
        break;

      case 'thread':
        $threadId = intval($constraintInfo);
        if ($threadId > 0)
        {
          return array(
            'metadata' => array('thread', $threadId)
          );
        }
        break;
    }

    return false;
  }

  /**
   * Gets the search form controller response for this type.
   *
   * @see XenForo_Search_DataHandler_Abstract::getSearchFormControllerResponse()
   */
  public function getSearchFormControllerResponse(XenForo_ControllerPublic_Abstract $controller, XenForo_Input $input, array $viewParams)
  {
    $params = $input->filterSingle('c', XenForo_Input::ARRAY_SIMPLE);

    $viewParams['search']['reply_count'] = empty($params['reply_count']) ? '' : $params['reply_count'];

    if (!empty($params['prefix']))
    {
      $viewParams['search']['prefixes'] = array_fill_keys(explode(' ', $params['prefix']), true);
    }
    else
    {
      $viewParams['search']['prefixes'] = array();
    }

    /** @var $threadPrefixModel XenForo_Model_ThreadPrefix */
    $threadPrefixModel = XenForo_Model::create('XenForo_Model_ThreadPrefix');

    $viewParams['prefixes'] = $threadPrefixModel->getPrefixesByGroups();
    if ($viewParams['prefixes'])
    {
      $visiblePrefixes = $threadPrefixModel->getVisiblePrefixIds();
      foreach ($viewParams['prefixes'] AS $key => &$prefixes)
      {
        foreach ($prefixes AS $prefixId => $prefix)
        {
          if (!isset($visiblePrefixes[$prefixId]))
          {
            unset($prefixes[$prefixId]);
          }
        }

        if (!count($prefixes))
        {
          unset($viewParams['prefixes'][$key]);
        }
      }
    }

    $viewParams['search']['thread'] = array();
    if (!empty($params['thread']))
    {
      $threadModel = $this->_getThreadModel();

      $thread = $threadModel->getThreadById($params['thread'], array(
        'join' => XenForo_Model_Thread::FETCH_FORUM,
        'permissionCombinationId' => XenForo_Visitor::getPermissionCombinationId(),
      ));

      if ($thread)
      {
        $permissions = XenForo_Permission::unserializePermissions($thread['node_permission_cache']);

        if ($threadModel->canViewThreadAndContainer($thread, $thread, $null, $permissions))
        {
          $viewParams['search']['thread'] = $this->_getThreadModel()->getThreadById($params['thread']);
        }
      }
    }

    return $controller->responseView('XenForo_ViewPublic_Search_Form_Post', 'search_form_post', $viewParams);
  }

  /**
   * Gets the search order for a type-specific search.
   *
   * @see XenForo_Search_DataHandler_Abstract::getOrderClause()
   */
  public function getOrderClause($order)
  {
    if ($order == 'replies')
    {
      return array(
        array('thread', 'reply_count', 'desc'),
        array('search_index', 'item_date', 'desc')
      );
    }
    else if ($order == 'threadmarks')
    {
      return array(
        array('thread', 'threadmark_count', 'desc'),
        array('search_index', 'item_date', 'desc')
      );
    }

    return false;
  }

  /**
   * Gets the necessary join structure information for this type.
   *
   * @see XenForo_Search_DataHandler_Abstract::getJoinStructures()
   */
  public function getJoinStructures(array $tables)
  {
    $structures = array();
    if (isset($tables['thread']))
    {
      $structures['thread'] = array(
        'table' => 'xf_thread',
        'key' => 'thread_id',
        'relationship' => array('search_index', 'discussion_id'),
      );
    }

    return $structures;
  }

  /**
   * Gets the content type that will be used when grouping for this type.
   *
   * @see XenForo_Search_DataHandler_Abstract::getGroupByType()
   */
  public function getGroupByType()
  {
    return 'thread';
  }

  protected function _getThreadMarkModel()
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