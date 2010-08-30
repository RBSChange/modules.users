<?php
/**
 * This action is used to call server from admin interface
 */
class users_GeneratePasswordAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$request->setAttribute('message', users_UserService::getInstance()->generatePassword());
		return self::getSuccessView();
	}
	
	/**
	 * @return string
	 */
	public function getRequestMethods()
	{
		return Request::GET;
	}
}