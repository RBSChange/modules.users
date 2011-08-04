<?php
class users_FrontendLogoutAction extends change_Action
{
    /**
	 * @param change_Context $context
	 * @param change_Request $request
	 * @return String
	 */
	public function _execute($context, $request)
	{
		users_UserService::getInstance()->authenticateFrontEndUser(null);
		users_ModuleService::getInstance()->unsetAutoLogin();
		$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getUrl();
		change_Controller::getInstance()->redirectToUrl($url);
		return change_View::NONE;
	}
    
	/**
	 * @return Boolean true
	 */
	public function isSecure()
	{
		return false;
	}
}