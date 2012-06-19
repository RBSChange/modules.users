<?php
class users_ResetPasswordAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$login = $request->getParameter('login');
		if (!f_util_StringUtils::isEmpty($login))
		{
			$us = users_userService::getInstance();
			try
			{
				$users = $us->getUsersByLoginAndGroup($login, users_BackendgroupService::getInstance()->getBackendGroup());
				if (count($users) != 1)
				{
					return $this->sendJSONError(LocaleService::getInstance()->trans('m.users.messages.error.logindoesnotexist', array('ucf')), false);
				}
				$user = $users[0];
				$us->prepareNewPassword($user);
				$result = array('message' => LocaleService::getInstance()->trans('m.users.bo.general.resetpassword.successtext', 
					array('ucf'), array('email' => $user->getEmail())));
					
				return $this->sendJSON($result);
			}
			catch (BaseException $e)
			{
				if ($e->getKey())
				{
					$error = f_Locale::translate('&' . $e->getKey() . ';', $e->getAttributes());
				}
				else
				{
					$error = $e->getMessage();
				}
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				$error = f_Locale::translate('&modules.users.frontoffice.resetpassword.Exception;');
			}
			
			return $this->sendJSONError($error, false);
		
		}
		return $this->sendJSONError(f_Locale::translate('&modules.users.messages.error.LoginDoesNotExist;'), false);
	}
	
	public function getRequestMethods()
	{
		return change_Request::POST;
	}
	
	public function isSecure()
	{
		return false;
	}
}