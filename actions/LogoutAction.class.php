<?php
class users_LogoutAction extends change_JSONAction
{
    /**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		users_UserService::getInstance()->authenticate(null);

		if (RequestContext::getInstance()->getMode() === RequestContext::BACKOFFICE_MODE)
		{
			return $this->sendJSON(array('disconected' => TRUE));
		}
		else
		{
			users_ModuleService::getInstance()->unsetAutoLogin();
			if (!$request->hasParameter('location'))
			{
				$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : website_WebsiteService::getInstance()->getCurrentWebsite()->getUrl();
				$request->setParameter('location', $url);
			}
			return change_Controller::getInstance()->forward('website', 'Redirect');
		}
    }
}