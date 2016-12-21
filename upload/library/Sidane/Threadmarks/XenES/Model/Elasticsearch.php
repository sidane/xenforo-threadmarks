<?php

class Sidane_Threadmarks_XenES_Model_Elasticsearch extends XFCP_Sidane_Threadmarks_XenES_Model_Elasticsearch
{
    // copied from XenES_Model_Elasticsearch, as it isn't extendable
    public function getOptimizableMappings(array $mappingTypes = null, $mappings = null)
    {
        if ($mappingTypes === null)
        {
            $mappingTypes = $this->getAllSearchContentTypes();
        }
        if ($mappings === null)
        {
            $mappings = $this->getMappings();
        }

        $optimizable = array();
        $searchContentTypes = XenForo_Model::create('XenForo_Model_Search')->getSearchDataHandlers();

        foreach ($mappingTypes AS $type)
        {
            if (!$mappings || !isset($mappings->$type)) // no index or no mapping
            {
                $optimize = true;
            }
            else
            {
                // our change
                $expectedMapping = static::$optimizedGenericMapping;
                if (isset($searchContentTypes[$type]) && is_callable(array($searchContentTypes[$type], 'getCustomMapping')))
                {
                    $expectedMapping = $searchContentTypes[$type]->getCustomMapping($expectedMapping);
                }
                $optimize = $this->_verifyMapping($mappings->$type, $expectedMapping);
            }

            if ($optimize)
            {
                $optimizable[] = $type;
            }
        }

        return $optimizable;
    }

    public function optimizeMapping($type, $deleteFirst = true, array $extra = array())
    {
        $extra = XenForo_Application::mapMerge(static::$optimizedGenericMapping, $extra);
        $handler = XenForo_Model::create('XenForo_Model_Search')->getSearchDataHandler($type);
        if (isset($handler) && is_callable(array($handler, 'getCustomMapping')))
        {
            $extra = $handler->getCustomMapping($extra);
        }
        parent::optimizeMapping($type, $deleteFirst, $extra);
    }

    protected $hasOptimizedIndex = false;
    public function recreateIndex()
    {
        parent::recreateIndex();
        if (!$this->hasOptimizedIndex)
        {
            $this->hasOptimizedIndex = true;
            $handlers = XenForo_Model::create('XenForo_Model_Search')->getSearchDataHandlers();
            foreach($handlers as $type => $handler)
            {
                if (is_callable(array($handler, 'getCustomMapping')))
                {
                    $this->optimizeMapping($type, true);
                }
            }
        }
    }
}