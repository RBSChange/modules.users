<?php
class users_BackenduserScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_backenduser
     */
    protected function initPersistentDocument()
    {
    	return users_BackenduserService::getInstance()->getNewDocumentInstance();
    }
    
	/**
	 * @see import_ScriptDocumentElement::getParentInTree()
	 */
	protected function getParentInTree()
	{
		return null;
	}
}