<?php
/**
 * users_GroupScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_GroupScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_group
     */
    protected function initPersistentDocument()
    {
    	return users_GroupService::getInstance()->getNewDocumentInstance();
    }
}