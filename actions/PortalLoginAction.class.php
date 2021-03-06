<?php
class users_PortalLoginAction extends users_ChromeLoginAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$login = trim($request->getParameter('login'));
		$password = trim($request->getParameter('password'));
		$result = array();
		if (empty($login) || empty($password))
		{
			$result['error'] = 'Invalid login or password';
		}
		else
		{
			$us = $this->getUserService();
			$user = $us->getIdentifiedBackendPortalUser($login, $password);
			if ($user !== null)
			{
				$us->authenticateBackEndUser($user);
				$result['ok'] = defined("PROJECT_ID") ? PROJECT_ID : PROFILE;
				$_SESSION['ChromeBaseUri'] = "rbschange/content/ext/" . $result['ok'];	
				$this->logAction($user);	
			}
			else
			{
				$result['error'] = 'Invalid authentification';
			}
		}
		echo JsonService::getInstance()->encode($result);
		return View::NONE;
	}
}