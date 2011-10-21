<?php
/**
 * users_DynamicgroupScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_DynamicgroupScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_dynamicgroup
     */
    protected function initPersistentDocument()
    {
    	return users_DynamicgroupService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/dynamicgroup');
	}
}