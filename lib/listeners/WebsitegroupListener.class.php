<?php
class users_WebsitegroupListener
{
	public function onPersistentDocumentUpdated($sender, $params)
	{
		if ($params['document'] instanceof website_persistentdocument_website)
		{
			$website = $params['document'];
			$wfgs = users_WebsitefrontendgroupService::getInstance();
			$wfgs->updateDefaultFromWebsite($website);
		}
	}	
	
	public function onPersistentDocumentCreated($sender, $params)
	{
		if ($params['document'] instanceof website_persistentdocument_website)
		{
			$website = $params['document'];
			$wfgs = users_WebsitefrontendgroupService::getInstance();
			$wfgs->createDefaultFromWebsite($website);
		}
	}
	
	public function onPersistentDocumentDeleted($sender, $params)
	{
		if ($params['document'] instanceof website_persistentdocument_website)
		{
			$website = $params['document'];
			$wfgs = users_WebsitefrontendgroupService::getInstance();
			$groups = $wfgs->getAllByWebsite($website);
			foreach ($groups as $group)
			{
				$wfgs->delete($group);
			}
		}		
	}
}