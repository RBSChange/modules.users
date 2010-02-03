<?php
class users_FrontendLogoutAction extends users_ActionBase
{
    /**
	 * @param Context $context
	 * @param Request $request
	 * @return String
	 */
	public function _execute($context, $request)
	{
		users_UserService::getInstance()->authenticateFrontEndUser(null);

		$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getUrl();
		HttpController::getInstance()->redirectToUrl($url);

		return View::NONE;
    }
    
	/**
	 * @return Boolean true
	 */
	public function isSecure()
	{
		return false;
	}
}