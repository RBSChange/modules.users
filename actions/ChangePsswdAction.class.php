<?php
class users_ChangePsswdAction extends f_action_BaseJSONAction 
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$oldPwd = $request->getParameter('oldPwd');
		$newPwd = $request->getParameter('newPwd');
		$newPwdConf = $request->getParameter('newPwdConf');
		
		// Test if all fields are ok
		if (f_util_StringUtils::isEmpty($oldPwd) || f_util_StringUtils::isEmpty($newPwd) || f_util_StringUtils::isEmpty($newPwdConf))
		{
			return $this->sendJSONError(f_Locale::translateUI('&modules.users.messages.error.AllFieldsAreRequired;'));
		}
		
		// Test if new password are the same
		if ($newPwd != $newPwdConf)
		{
			return $this->sendJSONError(f_Locale::translateUI('&modules.users.messages.error.NewPasswordsNotSames;'));
		}		
		
		$us = users_UserService::getInstance();
		$backendUser = $us->getCurrentBackEndUser();
		if (!users_UserService::getInstance()->checkIdentity($backendUser, $oldPwd))
		{
			return $this->sendJSONError(f_Locale::translateUI('&modules.users.messages.error.BadOldPassword;'));	
		}
		
		$us->resetPassword($backendUser, $newPwdConf);
		$succesmsg = f_Locale::translateUI('&modules.users.bo.general.ChangePassword.SuccessText;', array('email' => $backendUser->getEmail()));
		return $this->sendJSON(array('id' => $backendUser->getId(), 'message' => $succesmsg));
	}
}