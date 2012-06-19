<?php
class users_BackenduserScriptDocumentElement extends users_UserScriptDocumentElement
{
	
	/**
	 * @return users_persistentdocument_user
	 */	
	public function getPersistentDocument()
	{
		$pd = parent::getPersistentDocument();
		$pd->addGroups(users_BackendgroupService::getInstance()->getBackendGroup());
		return $pd;
	}
}