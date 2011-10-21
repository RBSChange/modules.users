<?php
/**
 * users_AuthenticateAction
 * @package modules.users.actions
 */
class users_AuthenticateAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 * @return string
	 */
	public function _execute($context, $request)
	{
		$us = users_UserService::getInstance();
		$login = $request->getParameter('login', $request->getModuleParameter('users', 'login'));
		$password = $request->getParameter('password', $request->getModuleParameter('users', 'password'));	
		$autoLogin = $request->getParameter('autoLogin', $request->getModuleParameter('users', 'autoLogin'));
			
		$website = website_WebsiteService::getInstance()->getCurrentWebsite();
		
		$user = $us->getIdentifiedUser($login, $password, $website->getGroup()->getId());
		if ($user !== null)
		{
			$us->authenticate($user);
			
			if ($autoLogin === 'yes')
			{
				users_ModuleService::getInstance()->setAutoLogin($user);
			}
		}
		else 
		{
			$error = LocaleService::getInstance()->transFO('m.users.frontoffice.authentication.badauthentication', array('ucf'));
			website_SessionMessage::addVolatileError($error);
				
			if ($request->hasParameter('errorlocation'))
			{
				$request->setParameter('location', $request->getParameter('errorlocation'));
			}
			if ($request->hasParameter('storageId'))
			{
				$storageId = $request->getParameter('storageId');
				$data = array('login' => $login, 'autoLogin' => $autoLogin);
				$this->getContext()->getStorage()->write($storageId, $data);
			}
		}	
			
		if (!$request->hasParameter('location'))
		{
			$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : website_WebsiteService::getInstance()->getCurrentWebsite()->getUrl();
			$request->setParameter('location', $url);
		}
		
		return change_Controller::getInstance()->forward('website', 'Redirect');
	}
	
	/**
	 * @return boolean false.
	 */
	public function isSecure()
	{
		return false;
	}	
}