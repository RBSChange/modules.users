<?php
/**
 * Class where to put your custom methods for document users_persistentdocument_dynamicfrontendgroup
 * @package modules.users.persistentdocument
 */
class users_persistentdocument_dynamicfrontendgroup extends users_persistentdocument_dynamicfrontendgroupbase 
{
	/**
	 * @return Array<String, Mixed>
	 */
	public function getParametersArray()
	{
		$parameters = $this->getParameters();
		if ($parameters)
		{
			return unserialize($parameters);
		}
		return array();
	}
	
	/**
	 * @param Array<String, Mixed> $array
	 */
	public function setParametersArray($array)
	{
		$this->setParameters(serialize($array));
	}
	
	/**
	 * @param String $key
	 * @return Mixed
	 */
	public function getParameter($key)
	{
		$parameters = $this->getParametersArray();
		if (array_key_exists($key, $parameters))
		{
			return $parameters[$key];
		}
		return null;
	}
	
	/**
	 * @param String $key
	 * @param Mixed $value
	 */
	public function setParameter($key, $value)
	{
		$parameters = $this->getParametersArray();
		$parameters[$key] = $value;
		$this->setParametersArray($parameters);
	}
	

}