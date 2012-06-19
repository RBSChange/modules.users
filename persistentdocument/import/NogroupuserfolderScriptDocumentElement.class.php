<?php
/**
 * users_NogroupuserfolderScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_NogroupuserfolderScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return users_persistentdocument_nogroupuserfolder
	 */
	protected function initPersistentDocument()
	{
		return users_NogroupuserfolderService::getInstance()->getNoGroupUserFolder();
	}
	
	/**
	 * @return users_persistentdocument_nogroupuserfoldermodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/nogroupuserfolder');
	}
}