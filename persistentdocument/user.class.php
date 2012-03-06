<?php
/**
 * users_persistentdocument_user
 * @package modules.users
 */
class users_persistentdocument_user extends users_persistentdocument_userbase
{
	
	/**
	 * @param String $email
	 * @return Boolean
	 */
	protected function setEmailInternal($email)
	{
		if ($email != null)
		{
			$email = f_util_StringUtils::strtolower(strval($email));
		}
		return parent::setEmailInternal($email);
	}
	
	private $password = null;
	private $generatepassword = false;
	
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
		if (parent::isValid())
		{				
			if (f_util_StringUtils::isNotEmpty($this->password))
			{
				$property = new validation_Property("password", $this->password);
				$passwordValidator = new validation_PasswordValidator();
				if (!$passwordValidator->validate($property, $this->validationErrors))
				{
					return false;
				}
			}
			return true;
		}
		return false;
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
	 * @return array<string, string>
	 */
	public function getTreeViewAttributeArray()
	{
		return array(
			'publicationstatus' => $this->getPublicationstatus(),
			'login' => $this->getLogin(),
			'label' => $this->getFullname()
		);
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
}