<?php
class users_ChromeLoginAction extends users_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
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
			$us = $this->getUserService();
			$user = $us->getBackEndUserByLogin($login);
			
			if ($user !== null && $user->getEmail() == null && $user->getPasswordmd5() == null)
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
					return View::NONE;
				}
			}
			
			$user = $us->getIdentifiedBackendUser($login, $password);
			if ($user !== null)
			{
				$us->authenticateBackEndUser($user);
				$result['ok'] = defined("PROJECT_ID") ? PROJECT_ID : PROFILE;
				$_SESSION['ChromeBaseUri'] = "rbschange/content/ext/" . $result['ok'];	
				$this->logAction($user);	
			}
			else
			{
				$result['error'] = f_Locale::translate('&modules.users.errors.Invalid-authentification;');
			}
		}
		echo JsonService::getInstance()->encode($result);
		return View::NONE;
	}

	public function isSecure()
	{
		return false;
	}
}