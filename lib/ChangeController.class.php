<?php
/**
 * @deprecated
 */
class users_ChangeController extends controller_ChangeController 
{
	const AUTO_LOGIN_COOKIE = 'autologin';
	
	/**
	 * @deprecated
	 */
	public function dispatch()
	{
		// Handle auto-login.
		$us = users_UserService::getInstance();
		if (is_null($us->getCurrentFrontEndUser()) && isset($_COOKIE[self::AUTO_LOGIN_COOKIE]))
		{
		    $autoLoginInfos = $_COOKIE[self::AUTO_LOGIN_COOKIE];
		    $login = $autoLoginInfos['login'];
		    $passwd = $autoLoginInfos['passwd'];
			
		    $website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		    $user = $us->getFrontendUserByLogin($login, $website->getId());
		    if ($user !== null && $user->isPublished() && sha1($user->getPasswordmd5()) == $passwd)
		    {
				if (Framework::isDebugEnabled())
				{
		    		Framework::debug(__METHOD__ . ' auto-login with ' . $login);
				}
		    	$us->authenticateFrontEndUser($user);
		    }
		    else if (Framework::isDebugEnabled())
			{
	    		Framework::debug(__METHOD__ . ' auto-login skipped because the user with login ' . $login . ' doesn\'t exist, is not published or the password is wrong.');
			}
		}
		
		parent::dispatch();
	}
	
	/**
	 * @deprecated
	 */
	public function allowAutoLogin()
	{
		return true;
	}
}