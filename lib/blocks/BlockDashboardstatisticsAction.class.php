<?php
class users_BlockDashboardstatisticsAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @see dashboard_BlockDashboardAction::setRequestContent()
	 *
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($forEdition) {return;}
	
		website_StyleService::getInstance()->registerStyle('modules.users.dashboard');
		$groupId = users_BackendgroupService::getInstance()->getBackendGroupId();
		
		$request->setAttribute('backendUserCount', users_UserService::getInstance()->getCountByGroupId($groupId));
		$request->setAttribute('backendUserPublishedCount', users_UserService::getInstance()->getPublishedCountByGroupId($groupId));
		$request->setAttribute('backendInactiveCount', users_UserService::getInstance()->getInactiveCountByGroupId($groupId));
		$request->setAttribute('backendInactiveWeekCount', users_UserService::getInstance()->getInactiveSinceDateCountByGroupId($groupId, date_Calendar::now()->sub(date_Calendar::DAY, 7)));
		$request->setAttribute('backendInactiveMonthCount', users_UserService::getInstance()->getInactiveSinceDateCountByGroupId($groupId, date_Calendar::now()->sub(date_Calendar::MONTH, 1)));	
		$websiteParam = intval($request->getParameter('website'));
		
		if ($websiteParam <= 0)
		{
			$websiteParam = null;
		}
		
	
		$websites = website_WebsiteService::getInstance()->createQuery()->find();
		$request->setAttribute('websites', $websites);
		foreach ($websites as $website)
		{
			if ($website->getId() != $websiteParam) {continue;}
			
			$website->selected = true;
			$groupId = $website->getGroup()->getId();
			$request->setAttribute('frontendUserCount', users_UserService::getInstance()->getCountByGroupId($groupId));
			$request->setAttribute('frontendUserPublishedCount', users_UserService::getInstance()->getPublishedCountByGroupId($groupId));
			$request->setAttribute('frontendInactiveCount', users_UserService::getInstance()->getInactiveCountByGroupId($groupId));
			$request->setAttribute('frontendInactiveWeekCount', users_UserService::getInstance()->getInactiveSinceDateCountByGroupId($groupId, date_Calendar::now()->sub(date_Calendar::DAY, 7)));
			$request->setAttribute('frontendInactiveMonthCount', users_UserService::getInstance()->getInactiveSinceDateCountByGroupId($groupId, date_Calendar::now()->sub(date_Calendar::MONTH, 1)));
			
		}		
	}
}