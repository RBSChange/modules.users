<?php
/**
 * users_persistentdocument_user
 * @package modules.users
 */
class users_persistentdocument_user extends users_persistentdocument_userbase
{
	/**
	 * @var string
	 */
	private $password = null;
	
	/**
	 * @var boolean
	 */
	private $generatepassword = false;
	
	/**
	 * @param string $email
	 * @return boolean
	 */
	protected function setEmailInternal($email)
	{
		if ($email != null)
		{
			$email = f_util_StringUtils::toLower(strval($email));
		}
		return parent::setEmailInternal($email);
	}
	
	/**
	 * @param list_persistentdocument_item $title
	 */
	public function setTitle($title)
	{
		if ($title instanceof list_persistentdocument_item)
		{
			$this->setTitleid($title->getId());
		}
		else 
		{
			$this->setTitleid(null);
		}
	}

	/**
	 * @return list_persistentdocument_item
	 */
	public function getTitle()
	{
		try
		{
			$titleId = $this->getTitleid();
			if ($titleId)
			{
				return DocumentHelper::getDocumentInstance($titleId, 'modules_list/item');
			}
		}
		catch ( Exception $e )
		{
			Framework::exception($e);
		}
		return null;
	}
	
	/**
	 * @return boolean 
	 */
	public function isValid()
	{
		parent::isValid();
		$this->isPasswordValid();
		return !$this->hasPropertiesErrors();
	}
	
	/**
	 * @return boolean 
	 */	
	protected function isPasswordValid()
	{
		if (f_util_StringUtils::isNotEmpty($this->password))
		{
			$securityLevel = users_GroupService::getInstance()->evaluateSecurityLevel($this->getGroupsArray());			 
			$c = change_Constraints::getByName('password', array('securityLevel' => $securityLevel));
			if (!$c->isValid($this->password))
			{
				$this->addPropertyErrors('password', change_Constraints::formatMessages($c));
				return false;
			}
		}	
		return true;
	}
	
	/**
	 * This method is used to set the password because it must be crypted with md5.
	 * @param string $newValue
	 * @throws ValidationException
	 */
	public function setPassword($newValue)
	{
		$this->setModificationdate(null);
		$this->password = $newValue;
	}
	
	/**
	 * Get the value of the clear password in user object.
	 * @return string
	 */
	public function getClearPassword()
	{
		return $this->password;
	}
	
	/**
	 * Reset the value of the clear password in user object.
	 */
	public function resetClearPassword()
	{
		$this->password = null;
	}
	
	/**
	 * Return the Fullname of the user composed by Name and Firstname (or email if both are empty).
	 * @return string
	 */
	public function getFullname()
	{
		$fullName = $this->getFirstname() . ' ' . $this->getLastname();
		if (f_util_StringUtils::isEmpty(trim($fullName)))
		{
			return $this->getEmail();
		}
		return $fullName;
	}
	
	/**
	 * Return the Fullname of the user composed by Name and Firstname (or email if both are empty).
	 * @return string
	 */
	public function getFullnameAsHtml()
	{
		$fullName = $this->getFirstnameAsHtml() . ' ' . $this->getLastnameAsHtml();
		if (f_util_StringUtils::isEmpty(trim($fullName)))
		{
			return $this->getEmailAsHtml();
		}
		return $fullName;
	}
	
	/**
	 * @return string[]
	 */
	public function getEmailAddresses()
	{
		return array($this->getEmail());
	}
	
	/**
	 * @return string
	 */
	public function getGeneratepassword()
	{
		return $this->generatepassword ? "true" : "false";
	}
	
	/**
	 * @param string $generatepassword
	 */
	public function setGeneratepassword($generatepassword)
	{
		$this->generatepassword = ($generatepassword == "true");
	}
	
	/**
	 * @return users_persistentdocument_usersprofile || null
	 */
	public function getUsersProfile()
	{
		return users_UsersprofileService::getInstance()->getByAccessorId($this->getId());
	}
	
	/**
	 * @param string $profilename
	 * @return users_persistentdocument_usersprofile || null
	 */
	public function getProfile($profilename = 'users')
	{
		return users_ProfileService::getInstance()->getByAccessorIdAndName($this->getId(), $profilename);
	}
	
	/**
	 * @return users_persistentdocument_usersprofile
	 */
	protected function getRequiredUsersProfile()
	{
		$profile = $this->getUsersProfile();
		if ($profile === null)
		{
			$profile = users_UsersprofileService::getInstance()->getNewDocumentInstance();
			$profile->setAccessor($this);
			$profile->save();
		}
		return $profile;
	}
	
	/**
	 * @return integer
	 */
	public function getTitleid()
	{
		$profile = $this->getUsersProfile();
		return ($profile !== null) ? $profile->getTitleid() : null;
	}

	/**
	 * @return string
	 */
	public function getFirstname()
	{
		$profile = $this->getUsersProfile();
		return ($profile !== null) ? $profile->getFirstname() : null;	
	}

	public function getFirstnameAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getFirstname());
	}
	
	/**
	 * @return string
	 */
	public function getLastname()
	{
		$profile = $this->getUsersProfile();
		return ($profile !== null) ? $profile->getLastname() : null;		
	}
	
	public function getLastnameAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getLastname());
	}
		
	/**
	 * @param string $name
	 * @param array $arguments
	 */
	final function __call($name, $arguments)
	{
		switch ($name)
		{
			case 'getDashboardcontent': 
				Framework::error('Call to deprecated ' . get_class($this) . '->'.$name.' function');
				$profile = dashboard_DashboardprofileService::getInstance()->getByAccessorId($this->getId());
				return ($profile !== null) ? $profile->getDashboardcontent() : null;		
			case 'setTitleid': 
				Framework::error('Call to Removed ' . get_class($this) . '->'.$name.' function');
				$this->getRequiredUsersProfile()->setTitleid($arguments[0]);
				return;
			case 'setFirstname': 
				Framework::error('Call to Removed ' . get_class($this) . '->'.$name.' function');
				$this->getRequiredUsersProfile()->setFirstname($arguments[0]);
				return;
			case 'setLastname': 
				Framework::error('Call to Removed ' . get_class($this) . '->'.$name.' function');
				$this->getRequiredUsersProfile()->setLastname($arguments[0]);
				return;
			case 'getWebsiteid': 
				Framework::error('Call to Removed ' . get_class($this) . '->'.$name.' function');
				$profile = $this->getUsersProfile();
				return ($profile !== null) ? $profile->getRegisteredwebsiteid() : website_WebsiteService::getInstance()->getCurrentWebsite()->getId();
			case 'setWebsiteid': 
				Framework::error('Call to Removed ' . get_class($this) . '->'.$name.' function');
				$this->getRequiredUsersProfile()->setRegisteredwebsiteid($arguments[0]);
				return;				
				
			case 'getTreeViewAttributeArray':
				Framework::error('Call to deprecated ' . get_class($this) . '->getTreeViewAttributeArray function');
				return array(
					'publicationstatus' => $this->getPublicationstatus(),
					'login' => $this->getLogin(),
					'label' => $this->getLabel()
				);				
			default: 
				throw new Exception('No method ' . get_class($this) . '->' . $name);
		}
	}
}