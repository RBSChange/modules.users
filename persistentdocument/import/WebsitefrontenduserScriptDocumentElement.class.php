<?php
/**
 * users_WebsitefrontenduserScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_WebsitefrontenduserScriptDocumentElement extends users_UserScriptDocumentElement
{
	/**
	 * @return users_persistentdocument_user
	 */
	protected function initPersistentDocument()
	{
		return users_UserService::getInstance()->getNewDocumentInstance();
	}
	
	public function getPersistentDocument()
	{
		$pd = parent::getPersistentDocument();
		$parentDocument = $this->getAncestorByClassName("users_WebsitefrontendgroupScriptDocumentElement");
		if ($parentDocument !== null)
		{
			if ($pd instanceof users_persistentdocument_user)
			{
				$pd->addGroups($parentDocument->getPersistentDocument());
			}
		}  
		return  $pd; 	
	}
		
	/**
	 * @see import_ScriptDocumentElement::getParentInTree()
	 */
	protected function getParentInTree()
	{
		return null;
	}
}