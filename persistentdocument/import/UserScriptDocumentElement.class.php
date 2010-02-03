<?php
/**
 * users_UserScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_UserScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_user
     */
    protected function initPersistentDocument()
    {
    	return users_UserService::getInstance()->getNewDocumentInstance();
    }
}