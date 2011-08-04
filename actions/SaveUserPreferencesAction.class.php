<?php

class users_SaveUserPreferencesAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		if ($user)
		{
			$userPreferences = $request->getParameter('userPreferences');
			$user->setMeta('userPreferences', $userPreferences);
			$user->saveMeta();
		}
		return $userPreferences;
	}
}