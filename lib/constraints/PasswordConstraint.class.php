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
	
 	/**
	 * @param array $params <documentId => integer, securityLevel => string || [parameter => integer,]>
	 */   
	public function __construct($params = array())
	{

		$messageTemplates = array(self::INVALID_PASSWORD =>
			LocaleService::getInstance()->trans('m.users.constraints.invalidpassword-' . $this->_securityLevel, array('ucf')));
		parent::__construct(array('messageTemplates' => $messageTemplates));
		
		$this->_levelArray = users_GroupService::getInstance()->getSecurityLevelArray();
		if (isset($params['documentId']) && intval($params['documentId']) > 0)
		{
			$this->_accessorId = intval($params['documentId']);
		}
		elseif (isset($params['parameter']))
		{
			if (intval($params['parameter']) > 0)
			{
				$this->_accessorId = intval($params['parameter']);
			}
			elseif(is_string($params['parameter']) && in_array($params['parameter'], $this->_levelArray))
			{
				$this->_securityLevel = $params['parameter'];
			}
		}
		
		if (isset($params['securityLevel']) && is_string($params['securityLevel']) && in_array($params['securityLevel'], $this->_levelArray))
		{
			$this->_securityLevel = $params['securityLevel'];
		}
		elseif ($this->_accessorId > 0)
		{
			$pp = f_persistentdocument_PersistentProvider::getInstance();
			$modelName = $pp->getDocumentModelName($this->_accessorId);
			if ($modelName)
			{
				$accessor = $pp->getDocumentInstance($this->_accessorId, $modelName);
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
			$this->error(self::INVALID_PASSWORD);
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