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
		
		// FIX #42282: use forBlockName to resolve conflicts between Resetpassword and Authentication...
        if ($request->hasParameter('submit') && (!$request->hasNonEmptyParameter('forBlockName') || $request->getParameter('forBlockName') == 'Resetpassword'))
        {
			$login = trim($request->getParameter('login'));
			if (!empty($login))
			{
				$us = users_userService::getInstance();
				$website = website_WebsiteService::getInstance()->getCurrentWebsite();
				
				try
				{
					$users = $us->getUsersByLoginAndGroup($login, $website->getGroup());
					if (count($users) == 1)
					{
						$us->prepareNewPassword($users[0]);
					}
					$request->setAttribute('loginAsHtml', f_util_HtmlUtils::textToHtml($login));
					return website_BlockView::SUCCESS;
				}
				catch (BaseException $e)
				{
					$this->addError($e->getLocaleMessage());
				}
			}
			else
			{
				$error = LocaleService::getInstance()->transFO('m.users.frontoffice.resetpassword.emptylogin', array('ucf'));
				$this->addError($error);
			}
		}
		return website_BlockView::INPUT;
	}
}