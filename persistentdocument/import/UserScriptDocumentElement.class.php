<?php
/**
 * users_UserScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_UserScriptDocumentElement extends import_ScriptDocumentElement
{
	protected $userProfileAttribute = array();
	
    /**
     * @return users_persistentdocument_user
     */
    protected function initPersistentDocument()
    {
    	return users_UserService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return users_persistentdocument_usermodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/user');
	} 

	/**
	 * @see import_ScriptDocumentElement::getParentInTree()
	 */
	protected function getParentInTree()
	{
		return null;
	}

	/**
	 * @see import_ScriptDocumentElement::getDocumentProperties()
	 *
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$props = parent::getDocumentProperties();
		
		if (isset($props['firstname']))
		{
			$this->userProfileAttribute['firstname'] = 	$props['firstname'];
			unset($props['firstname']);
		}
		
		if (isset($props['lastname']))
		{
			$this->userProfileAttribute['lastname'] = 	$props['lastname'];
			unset($props['lastname']);
		}
		
		if (!isset($props['publicationstatus']))
		{
			$props['publicationstatus'] = 'ACTIVE';
		}	
		return $props;
	}
	
	/**
	 * @return users_persistentdocument_user
	 */
 	public function getPersistentDocument()
    {
    	$pd = parent::getPersistentDocument();
    	$grp = $this->getAncestorByClassName('users_GroupScriptDocumentElement');
    	if ($grp !== null)
    	{
    		$pd->addGroups($grp->getPersistentDocument());
    	}
    	return $pd;
    }
    
	/**
	 * @see import_ScriptDocumentElement::saveDocument()
	 */
	protected function saveDocument()
	{
		parent::saveDocument();
		if (count($this->userProfileAttribute))
		{
			$user = $this->getPersistentDocument();
			$p = users_UsersprofileService::getInstance()->getByAccessorId($user->getId());
			if ($p === null)
			{
				$p = users_UsersprofileService::getInstance()->getNewDocumentInstance();
				$p->setAccessor($user);
			}
			if (isset($this->userProfileAttribute['firstname']))
			{
				$p->setFirstname($this->userProfileAttribute['firstname']);
			}
			if (isset($this->userProfileAttribute['lastname']))
			{
				$p->setLastname($this->userProfileAttribute['lastname']);
			}
			$p->save();
		}
	}
}