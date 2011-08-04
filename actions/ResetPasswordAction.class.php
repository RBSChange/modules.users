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
		if (! f_util_StringUtils::isEmpty($login))
		{
			$us = users_BackenduserService::getInstance();
			try
			{
				$user = $us->prepareNewPassword($login);
				$result = array('message' => f_Locale::translate('&modules.users.bo.general.ResetPassword.SuccessText;', array('email' => $user->getEmail())));
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