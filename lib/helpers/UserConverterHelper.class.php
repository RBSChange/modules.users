<?php
class users_UserConverterHelper
{
	/**
	 * @param Integer $frontUserId
	 * @param Integer $websiteId
	 */
	public static function convertFrontEndUserToWebsiteFronEndUser($frontUserId, $websiteId = null)
	{
		if ($websiteId === NULL)
		{
			$website = website_WebsiteModuleService::getInstance()->getDefaultWebsite();
		}
		else
		{
			$website = DocumentHelper::getDocumentInstance($websiteId);
		}
		
		if ($website instanceof website_persistentdocument_website && $website->getId() > 0)
		{
			$frontEndUser = DocumentHelper::getDocumentInstance($frontUserId);
			if ($frontEndUser instanceof users_persistentdocument_frontenduser && ! $frontEndUser instanceof users_persistentdocument_websitefrontenduser)
			{
				$wsfg = users_WebsitefrontendgroupService::getInstance();
				$websiteFrontEndgroup = $wsfg->getDefaultByWebsite($website);
				$documentmodelname = f_persistentdocument_PersistentDocumentModel::getInstance('users', 'websitefrontenduser')->getName();
				$wsfu = users_WebsitefrontenduserService::getInstance();
				
				$tm = f_persistentdocument_TransactionManager::getInstance();
				try
				{
					$tm->beginTransaction();
					$newfrontEndUser = $wsfu->transform($frontEndUser, $documentmodelname);
					self::moveWebsiteUser($newfrontEndUser, $websiteFrontEndgroup);
					$tm->commit();
				}
				catch (Exception $e)
				{
					$tm->rollBack($e);
				}
			}
			else
			{
				Framework::error(__METHOD__ . ' Invalid front end user type : ' . get_class($frontEndUser));
			}
		}
		else
		{
			Framework::error(__METHOD__ . ' Invalid websiteId : ' . $websiteId);
		}
	}
	
	/**
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @param users_persistentdocument_websitefrontendgroup $group
	 */
	private static function moveWebsiteUser($user, $group)
	{
		$user->setWebsiteid($group->getWebsiteid());
		$user->removeAllGroups();
		$user->addGroups($group);
		
		f_persistentdocument_PersistentProvider::getInstance()->updateDocument($user);
	}
}