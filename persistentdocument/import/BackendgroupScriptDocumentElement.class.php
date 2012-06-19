<?php
class users_BackendgroupScriptDocumentElement extends users_GroupScriptDocumentElement
{
	/**
	 * @return users_persistentdocument_backendgroup
	 */
	protected function initPersistentDocument()
	{
		return users_BackendgroupService::getInstance()->getBackendGroup();
	}
	
	/**
	 * @return users_persistentdocument_backendgroupmodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/backendgroup');
	}
}