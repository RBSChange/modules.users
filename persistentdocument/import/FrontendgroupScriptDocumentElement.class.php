<?php
class users_FrontendgroupScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_frontendgroup
     */
    protected function initPersistentDocument()
    {
    	return users_FrontendgroupService::getInstance()->getNewDocumentInstance();
    }
}
