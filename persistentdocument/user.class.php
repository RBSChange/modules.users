<?php
/**
 * users_persistentdocument_user
 * @package modules.users
 */
class users_persistentdocument_user extends users_persistentdocument_userbase
{
	/**
	 * Save the clear password here.
	 * @var string
	 */
	private $password = null;
	
	private $generatepassword = false;
	
	/**
	 * Validate the documentinstance
	 * @return Boolean 
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
	 * This method is used to set the password because it must be crypted with md5
	 *
	 * @param string $newValue
	 *
	 * @throws ValidationException
	 */
	public function setPassword($newValue)
	{
		$this->setModificationdate(null);
		$this->password = $newValue;
	}
	
	/**
	 * Get the value of the clear password in user object
	 * @return string
	 */
	public function getClearPassword()
	{
		return $this->password;
	}
	
	/**
	 * Reset the value of the clear password in user object
	 */
	public function resetClearPassword()
	{
		$this->password = null;
	}
	
	/**
	 * Return the Fullname of the user composed by Name and Firstname
	 * @return string
	 */
	public function getFullname()
	{
		return $this->getFirstname() . ' ' . $this->getLastname();
	}
	
	/**
	 * @return Array<String, String>
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
	 * @return Array<String>
	 */
	public function getEmailAddresses()
	{
		return array($this->getEmail());
	}
	
	/**
	 * @return String
	 */
	public function getGeneratepassword()
	{
		return $this->generatepassword ? "true" : "false";
	}
	
	/**
	 * @param String $generatepassword
	 */
	public function setGeneratepassword($generatepassword)
	{
		$this->generatepassword = ($generatepassword == "true");
	}

	
}