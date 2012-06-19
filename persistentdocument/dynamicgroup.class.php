<?php
/**
 * @package modules.users.persistentdocument
 */
class users_persistentdocument_dynamicgroup extends users_persistentdocument_dynamicgroupbase 
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
	 * @param string $key
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
	 * @param string $key
	 * @param Mixed $value
	 */
	public function setParameter($key, $value)
	{
		$parameters = $this->getParametersArray();
		$parameters[$key] = $value;
		$this->setParametersArray($parameters);
	}
	

}