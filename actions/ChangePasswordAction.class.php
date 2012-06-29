<?php
class users_ChangePasswordAction extends change_JSONAction 
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$oldPwd = $request->getParameter('oldPwd');
		$newPwd = $request->getParameter('newPwd');
		$newPwdConf = $request->getParameter('newPwdConf');
		$ls = LocaleService::getInstance();
		// Test if all fields are ok
		if (f_util_StringUtils::isEmpty($oldPwd) || f_util_StringUtils::isEmpty($newPwd) || f_util_StringUtils::isEmpty($newPwdConf))
		{
			return $this->sendJSONError($ls->trans('m.users.messages.error.allfieldsarerequired', array('ucf')));
		}
		
		// Test if new password are the same
		if ($newPwd != $newPwdConf)
		{
			return $this->sendJSONError($ls->trans('m.users.messages.error.newpasswordsnotsames', array('ucf')));
		}		
		
		$us = users_UserService::getInstance();
		$user = $us->getCurrentUser();
		if (!users_UserService::getInstance()->checkIdentity($user, $oldPwd))
		{
			return $this->sendJSONError($ls->trans('m.users.messages.error.badoldpassword', array('ucf')));	
		}
		
		$us->resetPassword($user, $newPwdConf);
		$succesmsg = $ls->trans('m.users.bo.general.changepassword.successtext', array('ucf'), array('email' => $user->getEmail()));
		$this->logAction($user);
		return $this->sendJSON(array('id' => $user->getId(), 'message' => $succesmsg));
	}
}