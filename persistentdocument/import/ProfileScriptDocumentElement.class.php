<?php
/**
 * users_ProfileScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
abstract class users_ProfileScriptDocumentElement extends import_ScriptDocumentElement
{
	
	protected function getProfileName()
	{
		return $this->getDocumentModel()->getModuleName();
	}
		
	/**
	 * @throws Exception
	 */
	protected function initPersistentDocument()
	{
		$userDoc = $this->getAncestorByClassName('users_UserScriptDocumentElement');
		if ($userDoc)
		{
			$user = $userDoc->getPersistentDocument();
		}
		else
		{
			$user = $this->getComputedAttribute('accessor', true);
		}
		
		if ($user)
		{
			$profile = users_ProfileService::getInstance()->createByAccessorAndName($user, $this->getProfileName());
		}
		else
		{
			throw new Exception('Invalid accessor');
		}
		return $profile;
	}
}