<?php
/**
 * users_AnonymoususerScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_AnonymoususerScriptDocumentElement extends users_UserScriptDocumentElement
{
    /**
     * @return users_persistentdocument_anonymoususer
     */
    protected function initPersistentDocument()
    {
    	return users_AnonymoususerService::getInstance()->getAnonymousUser();
    }
    
    /**
	 * @return users_persistentdocument_anonymoususermodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/anonymoususer');
	}
}