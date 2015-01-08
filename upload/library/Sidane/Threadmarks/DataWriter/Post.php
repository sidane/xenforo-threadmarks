<?php

class Sidane_Threadmarks_DataWriter_Post extends XFCP_Sidane_Threadmarks_DataWriter_Post
{
	protected function _messagePostSave()
	{
        parent::_messagePostSave();
                        
		if ($this->isChanged('message_state'))
		{
            $threadmark = $this->_getThreadMarkModel()->getByThreadIdAndPostId($this->get('thread_id'), $this->get('post_id'));
                      
            if (!empty($threadmark))
            {
                if ($this->get('message_state') == 'visible')
                {
                    $this->_getThreadMarkModel()->modifyThreadMarkCount($this->get('thread_id'), 1);
                }
                else if ($this->isUpdate() && $this->getExisting('message_state') == 'visible')
                {
                    $this->_getThreadMarkModel()->modifyThreadMarkCount($this->get('thread_id'), -1);
                }
            }
		}        
    }
    
	protected function _messagePostDelete()
	{
        $threadmark = $this->_getThreadMarkModel()->getByThreadIdAndPostId($this->get('thread_id'), $this->get('post_id'));
        if (!empty($threadmark))
        {
            $decrementCount = ($this->isUpdate() && $this->getExisting('message_state') == 'visible');
            $this->_getThreadMarkModel()->deleteThreadMark($threadmark['threadmark_id'], $decrementCount);
        }
	}    
    
    protected function _getThreadMarkModel()
    {
        return $this->getModelFromCache('Sidane_Threadmarks_Model_Threadmarks');
    }
}