<?php

class users_SaveUserPreferencesAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
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