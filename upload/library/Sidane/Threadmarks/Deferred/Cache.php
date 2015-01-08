<?php
  

class Sidane_Threadmarks_Deferred_Cache extends XenForo_Deferred_Abstract
{
	
	const PER_PAGE = 5000;
    
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
		$data = array_merge(array(
			'page' => 0,
		), $data);

		/** @var XenForo_Model_Post */
		$threadMarkModel = XenForo_Model::create('Sidane_Threadmarks_Model_Threadmarks');   
                
		$threadIds = $threadMarkModel->getThreadIdsWithThreadMarks($data['page'], self::PER_PAGE);
		
		if(empty($threadIds))
			return false;
			
		foreach($threadIds as $threadId)
        {
			$threadMarkModel->rebuildThreadMarkCache($threadId);
		}        
        
		$rbPhrase = new XenForo_Phrase('rebuilding');
		$typePhrase = new XenForo_Phrase('sidane_threadmarks_cache');
		$status = sprintf('%s... %s (%s)', $rbPhrase, $typePhrase, XenForo_Locale::numberFormat($data['page'] * self::PER_PAGE));

		$data['page'] ++;
		
		return $data;        
    }


	public function canCancel()
    {
		return true;
	}    
}