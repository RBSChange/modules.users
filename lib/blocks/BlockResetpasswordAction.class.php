<?php
class users_BlockResetpasswordAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::INPUT;
		}
		if ($request->hasParameter('submit'))
		{
			$errors = array();
			$login = trim($request->getParameter('login'));
			if (!empty($login))
			{
				$us = users_FrontenduserService::getInstance();
				$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
				try
				{
					$us->prepareNewPassword($login, $website->getId());
				}
				catch (BaseException $e)
				{
					$errors[] = $e->getLocaleMessage();
				}
				$this->setParameter("loginAsHtml", f_util_HtmlUtils::textToHtml($login));
			}
			else
			{
				$errors[] = LocaleService::getInstance()->transFO('m.users.frontoffice.resetpassword.emptylogin', array('ucf'));
			}

			if (count($errors) == 0)
			{
				return website_BlockView::SUCCESS;
			}
			$request->setAttribute('errors', $errors);
		}
		return website_BlockView::INPUT;
	}
}