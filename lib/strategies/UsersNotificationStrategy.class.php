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
	

	/* (non-PHPdoc)
	 * @see users_UsersNotificationStrategy::getNewAccountNotificationCodeByUser()
	 */
	public function getNewAccountNotificationCodeByUser($user)
	{
		// TODO Auto-generated method stub
		if ($user instanceof users_persistentdocument_frontenduser)
		{
			return 'modules_users/newFrontendUser';
		}
		if ($user instanceof users_persistentdocument_backenduser)
		{
			return 'modules_users/newBackendUser';
		}
		return null;
		
	}

	/* (non-PHPdoc)
	 * @see users_UsersNotificationStrategy::getPasswordChangeNotificationCodeByUser()
	 */
	public function getPasswordChangeNotificationCodeByUser($user)
	{
		if ($user instanceof users_persistentdocument_frontenduser)
		{
			 return 'modules_users/changeFrontendUserPassword';
		}
		if ($user instanceof users_persistentdocument_backenduser)
		{
			 return 'modules_users/changeBackendUserPassword';
		}
		return null;
	}

	/* (non-PHPdoc)
	 * @see users_UsersNotificationStrategy::getNotificationSubstitutions()
	 */
	public function getNotificationSubstitutions($user, $code)
	{
		if ($user instanceof users_persistentdocument_websitefrontenduser)
		{
			$accessLink = DocumentHelper::getDocumentInstance($user->getWebsiteid())->getUrl();
		}
		else if ($user instanceof users_persistentdocument_user)
		{
			$accessLink = Framework::getUIBaseUrl();
		}
		else
		{
			$accessLink = Framework::getUIBaseUrl();
		}
		return array(
			'login' => $user->getLogin(),
			'password' => $user->getClearPassword(),
			'accesslink' => $accessLink,
			'firstname' => $user->getFirstname(),
			'lastname' => $user->getLastname(),
			'fullname' => $user->getFullname(),
			'title' => $user->getTitle() ? $user->getTitle()->getLabel() : ''
		);
		
	}

	/* (non-PHPdoc)
	 * @see users_UsersNotificationStrategy::getNotificationWebsiteByUser()
	 */
	public function getNotificationWebsiteIdByUser($user)
	{
		if ($user instanceof users_persistentdocument_websitefrontenduser)
		{
			return $user->getWebsiteid();
		}
		return null;
	}

	
}