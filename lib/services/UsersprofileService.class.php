<?php
/**
 * @package modules.users
 * @method users_UsersprofileService getInstance()
 */
class users_UsersprofileService extends users_ProfileService
{
	/**
	 * @return users_persistentdocument_usersprofile
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_users/usersprofile');
	}

	/**
	 * Create a query based on 'modules_users/usersprofile' model.
	 * Return document that are instance of users_persistentdocument_usersprofile,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_users/usersprofile');
	}
	
	/**
	 * Create a query based on 'modules_users/usersprofile' model.
	 * Only documents that are strictly instance of users_persistentdocument_usersprofile
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_users/usersprofile', false);
	}
	
	/**
	 * @param integer $accessorId
	 * @param boolean $required
	 * @return users_persistentdocument_usersprofile || null
	 */
	public function getByAccessorId($accessorId, $required = false)
	{
		return parent::getByAccessorId($accessorId, $required);
	}
	
	/**
	 * @return users_persistentdocument_usersprofile
	 */
	public function getCurrent()
	{
		return parent::getCurrent();
	}
	
	/**
	 * @param users_persistentdocument_usersprofile $document
	 * @param string[] $propertiesName
	 * @param array $datas
	 * @param integer $accessorId
	 */
	public function addFormProperties($document, $propertiesName, &$datas, $accessorId = null)
	{
		if ($document->isNew()) {$datas['id'] = 0;}
		if ($document->getTitleid()) {$datas['titleid'] = $document->getTitleid();}
		if ($document->getFirstname()) {$datas['firstname'] = $document->getFirstname();}
		if ($document->getLastname()) {$datas['lastname'] = $document->getLastname();}

		$datas['displayname'] = ($document->getDisplayname() === true) ? 'true' : 'false';
		$datas['timezone'] = $document->getTimezone() === null ? DEFAULT_TIMEZONE : $document->getTimezone();
		
		if ($document->getDateformat()) {$datas['dateformat'] = $document->getDateformat();}
		if ($document->getDatetimeformat()) {$datas['datetimeformat'] = $document->getDatetimeformat();}
		if ($document->getLcid()) {$datas['lcid'] = $document->getLcid();}
		if ($document->getLocation()) {$datas['location'] = $document->getLocation();}
		if ($document->getBirthday()) {$datas['birthday'] = $document->getBirthday();}
		if ($document->getPersonnalwebsiteurl()) {$datas['personnalwebsiteurl'] = $document->getPersonnalwebsiteurl();}
	}
	
	/**
	 * @param users_persistentdocument_usersprofile $profile
	 * @return array
	 */
	protected function getSessionProperties($profile)
	{
		if ($profile !== null)
		{
			return array('timezone' => $profile->getTimezone(), 
				'dateformat' => $profile->getDateformat(),
				'datetimeformat' => $profile->getDatetimeformat(),
			);
		}
		return parent::getSessionProperties($profile);
	}
}