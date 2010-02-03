<?php
class users_FrontenduserScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_frontenduser
     */
    protected function initPersistentDocument()
    {
    	return users_FrontenduserService::getInstance()->getNewDocumentInstance();
    }
}
