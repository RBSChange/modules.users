<?php
/**
 * users_DynamicfrontendgroupScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_DynamicfrontendgroupScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_dynamicfrontendgroup
     */
    protected function initPersistentDocument()
    {
    	return users_DynamicfrontendgroupService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/dynamicfrontendgroup');
	}
}