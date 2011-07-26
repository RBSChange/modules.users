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

		$ls = LocaleService::getInstance();
		foreach ($users as $user)
		{
			$localeSuffix = ($user->getTitle() && preg_match('/(mme|mlle)/i', $user->getTitle()->getLabel())) ? 'female' : '';

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

			$lastLogin = date_Calendar::getInstance($user->getUILastlogin());
			$latestMinutes = date_Converter::convertDateToLocal(date_Calendar::getInstance()->sub(date_Calendar::MINUTE, 6));
			if ($lastLogin->isAfter($latestMinutes))
			{
				$status = $ls->transBO('m.users.bo.general.dashboard-statusnew', array('ucf'));
			}
			else if ($lastLogin->isToday())
			{
				$status = $ls->transBO("m.users.bo.general.dashboard-statussince$localeSuffix", array('ucf')) . ' ';
				$status .= $ls->transBO('m.uixul.bo.datePicker.calendar.today') . date_Formatter::format($lastLogin, ', H:i');
			}
			else
			{
				$status = $ls->transBO("m.users.bo.general.dashboard-statussince$localeSuffix", array('ucf')) . ' ';
				$status .= date_Formatter::toDefaultDateTimeBO($lastLogin);
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