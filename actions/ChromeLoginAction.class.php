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
		$result = array();
		if (empty($login) || empty($password))
		{		
			$result['error'] = f_Locale::translate('&modules.users.errors.Invalid-login-or-password;');
		}
		else
		{			
			$us = users_UserService::getInstance();
			$user = $us->getBackEndUserByLogin($login);
			
			if ($user !== null && $user->getEmail() == null && $user->getPasswordmd5() == null && !empty($adminemail))
			{
				try 
				{
					$this->getTransactionManager()->beginTransaction();
					$user->setEmail($adminemail);
					$user->setPassword($password);
					$user->save();
					$this->getTransactionManager()->commit();
				}
				catch (Exception $e)
				{
					$this->getTransactionManager()->rollBack($e);
					if ($e instanceof TransactionCancelledException) 
					{
						$e = $e->getSourceException();
					}
					
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
			
			$user = $us->getIdentifiedBackendUser($login, $password);
			if ($user !== null)
			{
				$us->authenticateBackEndUser($user);
				if ($request->hasParameter('uilang'))
				{
					$uilang = $request->getParameter('uilang');
					if (in_array($uilang, RequestContext::getInstance()->getUISupportedLanguages()))
					{
						change_Controller::getInstance()->getStorage()->write('uixul_uilang', $uilang);
					}
				}
				
				$result['ok'] = defined("PROJECT_ID") ? PROJECT_ID : Framework::getProfile();
				if ($user->getIsroot())
				{
					$result['OAuth'] = $this->getOAuthParams();
				}
				$uri =  "rbschange/content/ext/" . $result['ok'];
				change_Controller::getInstance()->getStorage()->write('uixul_ChromeBaseUri', $uri);
				$this->logAction($user);	
			}
			else
			{
				$result['error'] = f_Locale::translate('&modules.users.errors.Invalid-authentification;');
			}
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
	
	public function isSecure()
	{
		return false;
	}
}