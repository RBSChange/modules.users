<?php
/**
 * This action is used to call server from admin interface
 */
class users_GeneratePasswordAction extends users_ActionBase
{

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{

		// Generate a password
		$us = $this->getUserService();
		$request->setAttribute('message', $us->generatePassword());

        return self::getSuccessView();
	}

	public function getRequestMethods()
	{
		return Request::GET;
	}

}