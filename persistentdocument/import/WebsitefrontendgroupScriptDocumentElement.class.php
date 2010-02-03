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
		$website = null;
		$groupService = users_WebsitefrontendgroupService::getInstance();
		if (isset($this->attributes['for-default-website']) && $this->attributes['for-default-website'] == 'true')
		{
			$website = website_WebsiteModuleService::getInstance()->getDefaultWebsite();
			if ($website->isNew())
			{
				throw new Exception("No default website available");
			}
			$group = $groupService->getDefaultByWebsite($website);
			unset($this->attributes['for-default-website']);
			if ($group !== null)
			{
				return $group;
			}
		}
		$group = $groupService->getNewDocumentInstance();
		if ($website !== null)
		{
			$group->setWebsiteid($website->getId());
		}
		return $group;
	}
}