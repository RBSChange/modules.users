<?php
/**
 * users_UsersprofileScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_UsersprofileScriptDocumentElement extends users_ProfileScriptDocumentElement
{
	
	/**
	 * @see import_ScriptDocumentElement::getDocumentProperties()
	 */
	protected function getDocumentProperties()
	{
		$title = $this->getComputedAttribute('titleid');
		$registeredwebsite = $this->getComputedAttribute('registeredwebsiteid');
		
		$props = parent::getDocumentProperties();
		if ($title instanceof f_persistentdocument_PersistentDocument) 
		{
			$props['titleid'] = $title->getId();
		}
		elseif (is_numeric($title))
		{
			$props['titleid'] = $title;
		}
		
		if ($registeredwebsite instanceof website_persistentdocument_website) 
		{
			$props['registeredwebsiteid'] = $registeredwebsite->getId();
		}
		elseif (is_numeric($registeredwebsite)) 
		{
			$props['registeredwebsiteid'] = $registeredwebsite;
		}
		return $props;
	}

	/**
	 * @return users_persistentdocument_usersprofilemodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/usersprofile');
	}
}