<?php
/**
 * users_BlockDashboardConnectedUsersAction
 * @package modules.users.lib.blocks
 */
class users_BlockDashboardConnectedUsersAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($forEdition) {return;}
		
		$usersArray = array();
		$usersRedundantCountArray = array();

		$latestMinutes = date_Calendar::getInstance()->sub(date_Calendar::MINUTE, 6);		
		$users = users_UserService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::ge('lastping', $latestMinutes->toString()))
			->find();

		foreach ($users as $user)
		{
			$localeSuffix = ($user->getTitle() && preg_match('/(mme|mlle)/i', $user->getTitle()->getLabel())) ? 'Female' : '';

			$userName = $user->getFirstname() . ' ' . $user->getLastname();
			
			if (isset($usersArray[$userName]))
			{
				$usersRedundantCountArray[$userName]++;
				$userName = $userName . ' (' . $usersRedundantCountArray[$userName] . ')';
			}
			else
			{
				$usersRedundantCountArray[$userName] = 1;
			}

			$lastLogin = date_Calendar::getInstance($user->getLastlogin());
			$latestMinutes = date_Calendar::getInstance()->sub(date_Calendar::MINUTE, 6);

			if ($lastLogin->isAfter($latestMinutes))
			{
				$status = f_Locale::translateUI('&modules.users.bo.general.Dashboard-StatusNew;');
			}
			else if ($lastLogin->isToday())
			{
				$status = f_Locale::translateUI("&modules.users.bo.general.Dashboard-StatusSince$localeSuffix;") . ' ';
				$status .= f_Locale::translateUI('&modules.uixul.bo.datePicker.Calendar.today;') . date_DateFormat::format(date_Converter::convertDateToLocal($lastLogin), ', H:i');
			}
			else
			{
				$status = f_Locale::translateUI("&modules.users.bo.general.Dashboard-StatusSince$localeSuffix;");
				$status .= date_DateFormat::format(date_Converter::convertDateToLocal($lastLogin), ' l j F Y, H:i');
			}

			$currentUser = users_BackenduserService::getInstance()->getCurrentBackEndUser();
			$icon = MediaHelper::getIcon($user->getPersistentModel()->getIcon(), MediaHelper::SMALL);		
			$self = ($currentUser === $user) ? 'text-decoration: underline;' : '';

			$usersArray[$userName] = array(
				'self' => $self,
				'icon' => $icon,
				'name' => $userName,
				'status' => $status
			);
		}
		
		$request->setAttribute('users', $usersArray);
	}
}