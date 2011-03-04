<?php
/**
 * users_WebsitefrontendgroupScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_WebsitefrontendgroupScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return users_persistentdocument_websitefrontendgroup
	 */
	protected function initPersistentDocument()
	{
		$groupService = users_WebsitefrontendgroupService::getInstance();
		$website = $this->getComputedAttribute('website');
		
		if ($website === null && isset($this->attributes['for-default-website']) && $this->attributes['for-default-website'] == 'true')
		{
			$website = website_WebsiteModuleService::getInstance()->getDefaultWebsite();
			if ($website->isNew())
			{
				throw new Exception("No default website available");
			}
			unset($this->attributes['for-default-website']);
		}
		
		if ($website !== null)
		{
			$group = $groupService->getDefaultByWebsite($website);
			return $group;
		}
		else 
		{
			throw new Exception("No website specified");
		}
	}
}