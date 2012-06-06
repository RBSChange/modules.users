<?php
class users_ChromeLoginAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$login = trim($request->getParameter('login'));
		$password = trim($request->getParameter('password'));
		$adminemail = trim($request->getParameter('adminemail'));
		$backEndGroupID = users_BackendgroupService::getInstance()->getBackendGroupId(); 
		$us = users_UserService::getInstance();
		$authenticateUser = null;
		
		$result = array();
		if (empty($login) || empty($password))
		{		
			$result['error'] = LocaleService::getInstance()->trans('m.users.errors.invalid-login-or-password', array('ucf'));
		}
		elseif (!empty($adminemail))
		{
			$users = $us->getRootUsersByGroupId($backEndGroupID);
			foreach ($users as $user) 
			{
				/* @var $user users_persistentdocument_user */
				if ($user->getLogin() == $login && $user->getEmail() == null && $user->getPasswordmd5() == null)
				{
					try 
					{
						$this->getTransactionManager()->beginTransaction();
						$user->setEmail($adminemail);
						$user->setPassword($password);
						$user->save();
						
						$authenticateUser = $user;
						$this->getTransactionManager()->commit();
					}
					catch (Exception $e)
					{
						$this->getTransactionManager()->rollBack($e);
						if ($e instanceof ValidationException)
						{
							$pos = strpos($e->getMessage(), ':');
							if ($pos)
							{
								$result['error'] = substr($e->getMessage(), $pos + 1);
							}
							else
							{
								$result['error'] = $e->getMessage();
							}
						}
						else
						{
							$result['error'] = $e->getMessage();
						}
						echo JsonService::getInstance()->encode($result);
						return change_View::NONE;
					}
				}
			}	
		}
		else
		{			
			$authenticateUser = $us->getIdentifiedUser($login, $password, $backEndGroupID);
		}
		
		if ($authenticateUser !== null)
		{
			$us->authenticateBackEndUser($authenticateUser);
			if ($request->hasParameter('uilang'))
			{
				$uilang = $request->getParameter('uilang');
				if (in_array($uilang, RequestContext::getInstance()->getUISupportedLanguages()))
				{
					change_Controller::getInstance()->getStorage()->writeForUser('uilang', $uilang);
				}
			}
			
			$result['ok'] = defined("PROJECT_ID") ? PROJECT_ID : Framework::getProfile();
			if ($authenticateUser->getIsroot())
			{
				$result['OAuth'] = $this->getOAuthParams();
			}
			$uri =  "rbschange/content/ext/" . $result['ok'];
			change_Controller::getInstance()->getStorage()->write('uixul_ChromeBaseUri', $uri);
			$this->logAction($authenticateUser);	
		}
		else
		{
			$result['error'] = LocaleService::getInstance()->trans('m.users.errors.invalid-authentification', array('ucf'));
		}

		echo JsonService::getInstance()->encode($result);
		return change_View::NONE;
	}

	/**
	 * @return array
	 */
	private function getOAuthParams()
	{
		list($consumerKey, $consumerValue) = explode('#', file_get_contents(PROJECT_HOME . '/build/config/oauth/script/consumer.txt'));
		list($tokenKey, $tokenValue) = explode('#', file_get_contents(PROJECT_HOME . '/build/config/oauth/script/token.txt'));
		return array('consumerKey' => $consumerKey, 'consumerSecret' => $consumerValue,
		'token' => $tokenKey, 'tokenSecret' => $tokenValue);
	}
	
	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
}