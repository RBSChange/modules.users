<?php
class users_BlockResetpasswordAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::INPUT;
		}
		
		if ($request->hasParameter('submit'))
		{
			$login = trim($request->getParameter('login'));
			if (!empty($login))
			{
				$us = users_FrontenduserService::getInstance();
				$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
				try
				{
					$us->prepareNewPassword($login, $website->getId());
					$request->setAttribute('loginAsHtml', f_util_HtmlUtils::textToHtml($login));
					return website_BlockView::SUCCESS;
				}
				catch (BaseException $e)
				{
					$this->addError($e->getLocaleMessage());
					$request->setAttribute('errors', array($e->getLocaleMessage())); // For compatibility. Will be removed in 4.0.
				}
			}
			else
			{
				$error = LocaleService::getInstance()->transFO('m.users.frontoffice.resetpassword.emptylogin', array('ucf'));
				$this->addError($error);
				$request->setAttribute('errors', array($error)); // For compatibility. Will be removed in 4.0.
			}
		}
		return website_BlockView::INPUT;
	}
}