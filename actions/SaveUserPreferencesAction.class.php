<?php

class users_SaveUserPreferencesAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		$userPreferences = $request->getParameter('userPreferences');
		$p = users_ProfileService::getInstance()->createByAccessorAndName($user, 'dashboard');
		if ($p instanceof dashboard_persistentdocument_dashboardprofile) 
		{
			$p->setUserPreferences($userPreferences);
			$p->save();
		}
		return $userPreferences;
	}
}