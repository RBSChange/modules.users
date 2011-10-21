<?php
interface users_UsersNotificationStrategy
{
	public function getPasswordChangeNotificationCodeByUser($user);
	
	public function getNewAccountNotificationCodeByUser($user);
	
	public function getNotificationSubstitutions($user, $code);
	
	public function getNotificationWebsiteIdByUser($user);
}

class users_DefaultUsersNotificationStrategy implements users_UsersNotificationStrategy
{
	/**
	 * @param users_persistentdocument_user $user
	 * @return string
	 */
	public function getNewAccountNotificationCodeByUser($user)
	{
		if ($user->getGroupsCount())
		{
			if ($user->getGroups(0) instanceof users_persistentdocument_backendgroup)
			{
				return 'modules_users/newBackendUser';
			}
			else
			{
				return 'modules_users/newFrontendUser';
				
			}
		}
		return null;
		
	}

	/**
	 * @param users_persistentdocument_user $user
	 * @return string
	 */
	public function getPasswordChangeNotificationCodeByUser($user)
	{
		if ($user->getGroupsCount())
		{
			if ($user->getGroups(0) instanceof users_persistentdocument_backendgroup)
			{
				 return 'modules_users/changeBackendUserPassword';
			}
			else
			{
				 return 'modules_users/changeFrontendUserPassword';
			}	
		}
		return null;
	}

	/**
	 * @param users_persistentdocument_user $user
	 * @param string $code
	 * @return array
	 */
	public function getNotificationSubstitutions($user, $code)
	{
		$accessLink = website_WebsiteService::getInstance()->getCurrentWebsite()->getUrl();
		
		$t = $user->getTitle();
		return array(
			'login' => $user->getLogin(),
			'password' => $user->getClearPassword(),
			'accesslink' => $accessLink,
			'firstname' => $user->getFirstname(),
			'lastname' => $user->getLastname(),
			'fullname' => $user->getLabel(),
			'title' => $t ? $t->getLabel() : ''
		);
		
	}

	/**
	 * @param users_persistentdocument_user $user
	 * @return integer
	 */
	public function getNotificationWebsiteIdByUser($user)
	{
		foreach ($user->getGroupsArray() as $grp) 
		{
			if (!($grp instanceof users_persistentdocument_backendgroup)) 
			{
				$websites = website_WebsiteService::getInstance()->createQuery()
					->add(Restrictions::eq('group', $grp))
					->setProjection(Projections::property('id', 'id'))
					->findColumn('id');
				if (count($websites))
				{
					return $websites[0];
				}
			}
		}
		return null;
	}
}