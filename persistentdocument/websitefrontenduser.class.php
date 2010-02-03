<?php
class users_persistentdocument_websitefrontenduser extends users_persistentdocument_websitefrontenduserbase
{
	
	/**
	 * @param Integer $index
	 * @param users_persistentdocument_frontendgroup $newValue Can't not be null
	 * @return void
	 */
	public function setGroups($index, $newValue)
	{
		if ($newValue instanceof users_persistentdocument_frontendgroup)
		{
			parent::setGroups($index, $newValue);
		}		
		
	}
	
	/**
	 * @param users_persistentdocument_frontendgroup $newValue  Can't not be null
	 * @return void
	 */
	public function addGroups($newValue)
	{
		if ($newValue instanceof users_persistentdocument_frontendgroup)
		{
			parent::addGroups($newValue);
		}
	}
	
	public function setWebsite($website)
	{
		if ($website === null)
		{
			$this->setWebsiteId(null);
		}
		else
		{
			$this->setWebsiteId($website->getId());
		}
	}
}