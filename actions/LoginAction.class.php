<?php
class users_LoginAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		if (RequestContext::getInstance()->getMode() == RequestContext::FRONTOFFICE_MODE)
		{
			return change_Controller::getInstance()->forward('website', 'Error401');
		}
		
		if (!RequestContext::getInstance()->inHTTPS() && DEFAULT_UI_PROTOCOL === 'https')
		{
			change_Controller::getInstance()->redirectToUrl(LinkHelper::getUIActionLink('users', 'Login')->getUrl());
			return null;
		}
		return change_View::INPUT;
	}

	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
}