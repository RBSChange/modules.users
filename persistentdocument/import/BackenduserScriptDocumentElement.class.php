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
}