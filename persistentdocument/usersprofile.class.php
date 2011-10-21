<?php
/**
 * Class where to put your custom methods for document users_persistentdocument_usersprofile
 * @package modules.users.persistentdocument
 */
class users_persistentdocument_usersprofile extends users_persistentdocument_usersprofilebase
{
	/**
	 * @return string
	 */
	public function getBirthday()
	{
		if (f_util_StringUtils::isNotEmpty($this->getBirthyear()) && f_util_StringUtils::isNotEmpty($this->getBirthmonthandday()))
		{
			return $this->getBirthyear() . '-' . $this->getBirthmonthandday() . ' 00:00:00';
		}	
		return null;	
	}
	
	/**
	 * @param string $string
	 */
	public function setBirthday($string)
	{
		if (f_util_StringUtils::isNotEmpty($string))
		{
			$date = date_Calendar::getInstance($string);
			$this->setBirthyear($date->getYear());
			$this->setBirthmonthandday(date_Formatter::format($date, 'm-d'));
		}
		else
		{
			$this->setBirthyear(null);
			$this->setBirthmonthandday(null);
		}
	}
}