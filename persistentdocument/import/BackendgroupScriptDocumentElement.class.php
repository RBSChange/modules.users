<?php
class users_BackendgroupScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_backendgroup
     */
    protected function initPersistentDocument()
    {
    	return users_BackendgroupService::getInstance()->getNewDocumentInstance();
    }
}