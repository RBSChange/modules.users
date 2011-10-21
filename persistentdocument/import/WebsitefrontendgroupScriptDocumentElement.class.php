<?php
/**
 * users_WebsitefrontendgroupScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_WebsitefrontendgroupScriptDocumentElement extends users_GroupScriptDocumentElement
{
	/**
	 * @return users_persistentdocument_group
	 */
	protected function initPersistentDocument()
	{
		$groupService = users_GroupService::getInstance();
		$website = $this->getComputedAttribute('website');	
			
		if ($website === null && isset($this->attributes['for-default-website']) && $this->attributes['for-default-website'] == 'true')
		{
			$website = website_WebsiteService::getInstance()->getDefaultWebsite();
			if ($website->isNew())
			{
				throw new Exception("No default website available");
			}
			unset($this->attributes['for-default-website']);
		}
		
		if ($website !== null)
		{
			return $website->getGroup();
		}
		else 
		{
			throw new Exception("No website specified");
		}
	}
}