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

	/**
	 * @param users_persistentdocument_user $user
	 * @return string
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

	/**
	 * @param users_persistentdocument_user $user
	 * @param string $code
	 * @return array
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

	/**
	 * @param users_persistentdocument_user
	 * @return integer
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