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
		
		$request->setAttribute('backendUserCount', users_BackenduserService::getInstance()->getCount());
		$request->setAttribute('backendUserPublishedCount', users_BackenduserService::getInstance()->getPublishedCount());
		$request->setAttribute('backendInactiveCount', users_BackenduserService::getInstance()->getInactiveCount());
		$request->setAttribute('backendInactiveWeekCount', users_BackenduserService::getInstance()->getInactiveSinceDateCount(date_Calendar::now()->sub(date_Calendar::DAY, 7)));
		$request->setAttribute('backendInactiveMonthCount', users_BackenduserService::getInstance()->getInactiveSinceDateCount(date_Calendar::now()->sub(date_Calendar::MONTH, 1)));	
		$websiteParam = intval($request->getParameter('website'));
		
		if ($websiteParam <= 0)
		{
			$websiteParam = null;
		}
		
		$request->setAttribute('frontendUserCount', users_WebsitefrontenduserService::getInstance()->getCount($websiteParam));
		$request->setAttribute('frontendUserPublishedCount', users_WebsitefrontenduserService::getInstance()->getPublishedCount($websiteParam));
		$request->setAttribute('frontendInactiveCount', users_WebsitefrontenduserService::getInstance()->getInactiveCount($websiteParam));
		$request->setAttribute('frontendInactiveWeekCount', users_WebsitefrontenduserService::getInstance()->getInactiveSinceDateCount(date_Calendar::now()->sub(date_Calendar::DAY, 7),$websiteParam));
		$request->setAttribute('frontendInactiveMonthCount', users_WebsitefrontenduserService::getInstance()->getInactiveSinceDateCount(date_Calendar::now()->sub(date_Calendar::MONTH, 1), $websiteParam));
	
		$websites = website_WebsiteService::getInstance()->createQuery()->find();
		$request->setAttribute('websites', $websites);
		foreach ($websites as $website)
		{
			$website->selected = ($website->getId() == $websiteParam);
		}		
	}
}