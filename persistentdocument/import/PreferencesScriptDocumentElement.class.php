<?php
class users_PreferencesScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return users_persistentdocument_preferences
     */
    protected function initPersistentDocument()
    {
    	$document = ModuleService::getInstance()->getPreferencesDocument('users');
    	return ($document !== null) ? $document : users_PreferencesService::getInstance()->getNewDocumentInstance();
    }
}