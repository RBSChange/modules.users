<?php
class users_ChangeController extends change_Controller 
{
	const AUTO_LOGIN_COOKIE = 'autologin';
	
	/**
	 * @return void
	 */
	public function dispatch()
	{
		// Handle auto-login.
		$us = users_UserService::getInstance();
		if (isset($_COOKIE[self::AUTO_LOGIN_COOKIE]) && $us->getCurrentUser() === null)
		{
		    $autoLoginInfos = $_COOKIE[self::AUTO_LOGIN_COOKIE];
		    $login = $autoLoginInfos['login'];
		    $passwd = $autoLoginInfos['passwd'];
			
		    $website = website_WebsiteService::getInstance()->getCurrentWebsite();
		    $users = $us->getUsersByLoginAndGroup($login, $website->getGroup());
		    $ok = false;
		    foreach ($users as $user) 
		    {
			    if (sha1($user->getPasswordmd5()) == $passwd)
			    {
					if (Framework::isInfoEnabled())
					{
			    		Framework::info(__METHOD__ . ' auto-login with ' . $login);
					}
			    	$us->authenticate($user);
			    	$ok = true;
			    	break;
			    }
		    }
		    
		    if (!$ok)
			{
				if (Framework::isInfoEnabled())
				{
	    			Framework::info(__METHOD__ . ' auto-login skipped because the user with login ' . $login . ' doesn\'t exist, is not published or the password is wrong.');
				}
				users_ModuleService::getInstance()->unsetAutoLogin();
			}
		}
		parent::dispatch();
	}
	
	/**
	 * @return Boolean
	 */
	public function allowAutoLogin()
	{
		return true;
	}
}