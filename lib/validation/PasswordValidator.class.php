<?php
/**
 * @package framework.validation
 */
class validation_PasswordValidator extends validation_ValidatorImpl implements validation_Validator
{
	const SECURITY_LEVEL_LOW = 'low';
	const SECURITY_LEVEL_MEDIUM = 'medium';
	const SECURITY_LEVEL_HIGH = 'high';
	const SECURITY_LEVEL_MINIMAL = 'minimal';
	
	private $securityLevel;

	/**
	 * 
	 * @param users_persistentdocument_user $user
	 */
	public function __construct($user = null)
	{
		if ($user !== null)
		{
			$this->securityLevel = $this->getSecurityLevelByDoc($user);
		}
	}
	
	private function getSecurityLevelByDoc($document)
	{
		if ($document instanceof users_persistentdocument_user)
		{
			$levels = array('minimal', 'low', 'medium', 'high');
			$lvlIndex = null;
		
			foreach ($document->getGroupsArray() as $group) 
			{
				/* @var $group users_persistentdocument_group */
				$lvl = $group->getSecuritylevel();
				if (!empty($lvl))
				{
					$grpLvlIndex = array_search($lvl, $levels);
					if ($grpLvlIndex !== false && ($lvlIndex === null || $grpLvlIndex < $lvlIndex))
					{
						$lvlIndex = $grpLvlIndex;
					}
				}
			}
			return ($lvlIndex === null) ? null : $levels[$lvlIndex];
		}
		elseif ($document instanceof users_persistentdocument_group)	
		{
			return $document->getSecuritylevel();
		}
		return null;
	}
	
	
	/**
	 * @return string
	 */
	private function getSecurityLevel()
	{
		if ($this->securityLevel === null)
		{
			$securityLevel = $this->getParameter();
			if (is_numeric($securityLevel))
			{
				try 
				{
					$document = DocumentHelper::getDocumentInstance($securityLevel);
					$securityLevel = $this->getSecurityLevelByDoc($document);
				}
				catch (Exception $e)
				{
					Framework::exception($e);
					$securityLevel = self::SECURITY_LEVEL_MINIMAL;
				}
			}

			if ((is_bool($securityLevel) && $securityLevel) || empty($securityLevel) || !in_array($securityLevel, array('minimal', 'low', 'medium', 'high')))
			{
				$securityLevel = self::SECURITY_LEVEL_MINIMAL;
			}
			$this->securityLevel =  $securityLevel;
		}
		return $this->securityLevel;
	}
	
	/**
	 * Returns the error message.
	 * @param array|null $args
	 * @return string
	 */
	protected function getMessage($args = null)
	{
		$code = $this->getMessageCode();
		
		if ($this->getSecurityLevel())
		{
			$code = str_replace('.message', '.message.' . strtolower($this->getSecurityLevel()), $code);
		}
		return LocaleService::getInstance()->trans($code, array('ucf'), array('param' => $this->getParameter()));
	}
	
	/**
	 * Validate $data and append error message in $errors.
	 *
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		$securityLevel = $this->getSecurityLevel();
		switch ($securityLevel)
		{
			case self::SECURITY_LEVEL_LOW:
				$validate = $this->doValidateLow($field->getValue());
				break;
			case self::SECURITY_LEVEL_MEDIUM:
				$validate = $this->doValidateMedium($field->getValue());
				break;
			case self::SECURITY_LEVEL_HIGH:
				$validate = $this->doValidateHigh($field->getValue());
				break;
			case self::SECURITY_LEVEL_MINIMAL:
				$validate = $this->doValidateMinimal($field->getValue());
				break;
			default:
				throw new ValidatorConfigurationException(__CLASS__ . ' must have a valid parameter: value must be "' . self::SECURITY_LEVEL_MINIMAL . '", "' . self::SECURITY_LEVEL_LOW . '", "' . self::SECURITY_LEVEL_MEDIUM . '" or "' . self::SECURITY_LEVEL_HIGH . '"');
		}
		if (!$validate)
		{
			$this->reject($field->getName(), $errors);
		}
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
		return true && $this->doValidateLow($password) && f_util_StringUtils::containsLetter($password) && f_util_StringUtils::containsDigit($password);
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
		return true && $this->doValidateLow($password) && f_util_StringUtils::containsUppercasedLetter($password) && f_util_StringUtils::containsLowercasedLetter($password) && f_util_StringUtils::containsDigit($password);
	}
}