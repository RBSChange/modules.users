<?php
class change_PasswordConstraint extends \Zend\Validator\AbstractValidator
{
	const INVALID_PASSWORD = 'invalidPassword';
	
	protected $_levelArray;
	
	 /**
	 * @var integer
	 */
	protected $_accessorId = 0;
	
	/**
	 * @var string
	 */
	protected $_securityLevel = 'minimal';
	
	
	public function setSecurityLevel($securityLevel)
	{
		if (in_array($securityLevel, $this->_levelArray))
		{
			$this->_securityLevel = $securityLevel;
		}
	}

	public function getSecurityLevel()
	{
		return $this->_securityLevel;
	}
	
	public function setDocumentId($documentId)
	{
		$this->_accessorId = intval($documentId);
	}
	
	public function getDocumentId()
	{
		return $this->_accessorId;
	}
	
	public function setParameter($parameter)
	{
		if (in_array($parameter, $this->_levelArray))
		{
			$this->setSecurityLevel($parameter);
		}
		elseif (intval($parameter) > 0)
		{
			$this->setDocumentId($parameter);
		}
	}
	
	
 	/**
	 * @param array $params <documentId => integer, securityLevel => string || [parameter => integer,]>
	 */   
	public function __construct($params = array())
	{
		$params += change_Constraints::getDefaultOptions();
		$params['translatorTextDomain'] = 'm.users.constraints';
		$this->_levelArray = users_GroupService::getInstance()->getSecurityLevelArray();
		$this->messageTemplates = array();
		foreach ($this->_levelArray as $level)
		{
			$this->messageTemplates[self::INVALID_PASSWORD.'-'.$level] = $level;
		}
		parent::__construct($params);

		if ($this->_accessorId > 0)
		{
			$accessor = DocumentHelper::getDocumentInstanceIfExists($this->_accessorId);
			if ($accessor instanceof users_persistentdocument_group)	
			{
				if (in_array($accessor->getSecuritylevel(), $this->_levelArray))
				{
					$this->_securityLevel = $accessor->getSecuritylevel();
				}
			}
			elseif ($accessor instanceof users_persistentdocument_user)
			{
				$lvlIndex = null;				
				foreach ($accessor->getGroupsArray() as $group) 
				{
					/* @var $group users_persistentdocument_group */
					$lvl = $group->getSecuritylevel();
					if (!empty($lvl))
					{
						$grpLvlIndex = array_search($lvl, $this->_levelArray);
						if ($grpLvlIndex !== false && ($lvlIndex === null || $grpLvlIndex < $lvlIndex))
						{
							$lvlIndex = $grpLvlIndex;
						}
					}
				}
				if ($lvlIndex !== null)
				{
					$this->_securityLevel = $this->_levelArray[$lvlIndex];
				}
			}
		}
	}
	
	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->setValue($value);
		switch ($this->_securityLevel)
		{
			case 'low':
				$valid = $this->doValidateLow($value);
				break;
			case 'medium':
				$valid = $this->doValidateMedium($value);
				break;
			case 'high':
				$valid = $this->doValidateHigh($value);
				break;
			default:
				$valid = $this->doValidateMinimal($value);
		}		
		if (!$valid)
		{
			$this->error(self::INVALID_PASSWORD.'-'.$this->_securityLevel);
			return false;
		}
		return true;
	}   

	/**
	 * Password check: 1 char min 
	 * Security level: minimal.
	 *
	 * @param string $password
	 * @return boolean
	 */
	private function doValidateMinimal($password)
	{
		return f_util_StringUtils::strlen($password) >= 1;
	}
	
	/**
	 * Password check: 6 chars min 
	 * Security level: low.
	 *
	 * @param string $password
	 * @return boolean
	 */
	private function doValidateLow($password)
	{
		return f_util_StringUtils::strlen($password) >= 6;
	}

	/**
	 * Password check: 6 chars min with letters and digits.
	 * Security level: medium.
	 *
	 * @param string $password
	 * @return boolean
	 */
	private function doValidateMedium($password)
	{
		return $this->doValidateLow($password) && 
			f_util_StringUtils::containsLetter($password) && 
			f_util_StringUtils::containsDigit($password);
	}

	/**
	 * Password check: 6 chars min with uppercased letters, lowercased letters and digits.
	 * Security level: high.
	 *
	 * @param string $password
	 * @return boolean
	 */
	private function doValidateHigh($password)
	{
		return $this->doValidateMedium($password) && 
			f_util_StringUtils::containsUppercasedLetter($password) && 
			f_util_StringUtils::containsLowercasedLetter($password);
	}
}