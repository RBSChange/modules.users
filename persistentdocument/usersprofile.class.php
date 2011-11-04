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
	
	/**
	 * @return string
	 */
	public function getFOBirthday()
	{
		if (f_util_StringUtils::isNotEmpty($this->getBirthyear()) && f_util_StringUtils::isNotEmpty($this->getBirthmonthandday()))
		{
			list ($month, $day) = explode('-', $this->getBirthmonthandday());
			return $day . '/' . $month . '/' . $this->getBirthyear();
		}	
		return null;	
	}
	
	/**
	 * @param string $string
	 */
	public function setFOBirthday($string)
	{
		if (f_util_StringUtils::isNotEmpty($string))
		{
			$matches = array();
			if (preg_match('#^([0-9]{2})/([0-9]{2})/([0-9]{4})$#', $string, $matches))
			{
				$this->setBirthyear($matches[3]);
				$this->setBirthmonthandday($matches[2] . '-' . $matches[1]);
				return;
			}
		}
		$this->setBirthyear(null);
		$this->setBirthmonthandday(null);
	}
	
	/**
	 * @return string
	 */
	public function getShortPersonnalwebsiteurl()
	{
		$shortUrl = $this->getPersonnalwebsiteurl();
		if (f_util_StringUtils::strlen($shortUrl) > 33)
		{
			$shortUrl = f_util_StringUtils::substr($shortUrl, 0, 13) . '.....' . f_util_StringUtils::substr($shortUrl, -13);
		}
		return $shortUrl;
	}
	
	/**
	 * @param integer $size
	 * @param string $defaultImageUrl
	 * @param string $rating
	 * @return string
	 */
	public function getGravatarUrl($size = '32', $defaultImageUrl = '', $rating = 'g')
	{
		$accessor = $this->getAccessorIdInstance();
		if (!($accessor instanceof users_persistentdocument_user))
		{
			return null;
		}
		$url = 'http://www.gravatar.com/avatar/' . md5($this->getAccessorIdInstance()->getEmail()) . '?s=' . $size . '&amp;r=' . $rating;
		if ($defaultImageUrl)
		{
			$url .= '&amp;d=' . urlencode($defaultImageUrl);
		}
		return $url;
	}
}