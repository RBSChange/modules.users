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
		
		$latestMinutes = date_Calendar::getInstance()->sub(date_Calendar::MINUTE, 6);		
		$users = users_UserService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::ge('lastping', $latestMinutes->toString()))
			->addOrder(Order::desc('lastlogin'))
			->find();

		$ls = LocaleService::getInstance();
		$usersArray = array();
		foreach ($users as $user)
		{
			if (date_Calendar::getInstance($user->getLastlogin())->isAfter($latestMinutes))
			{
				$status = $ls->trans('m.users.bo.general.dashboard-statusnew', array('ucf'));
			}
			else
			{
				$status = $ls->trans('m.users.bo.general.dashboard-statussince', array('ucf', 'lab')) . ' ';
				$status .= date_Formatter::toDefaultDateTimeBO($user->getUILastlogin());
			}
			$currentUser = users_UserService::getInstance()->getCurrentBackEndUser();
			$usersArray[] = array(
				'self' => ($currentUser === $user) ? 'text-decoration: underline;' : '',
				'icon' => MediaHelper::getIcon($user->getPersistentModel()->getIcon(), MediaHelper::SMALL),
				'name' => $user->getLabel(),
				'status' => $status
			);
		}
		
		$request->setAttribute('users', $usersArray);
	}
}