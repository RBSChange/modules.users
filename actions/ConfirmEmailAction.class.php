<?php
/**
 * users_ConfirmEmailAction
 * @package modules.users.actions
 */
class users_ConfirmEmailAction extends f_action_BaseAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{	
		$user = $this->getUserFromRequest($request);
		$key = $request->getParameter('key');
		if ($key === null || $user === null)
		{
			Controller::getInstance()->redirect('website', 'Error500');
		}
		try
		{
			if ($user->getDocumentService()->confirmEmail($user, $key))
			{
				users_UserService::getInstance()->authenticateFrontEndUser($user);
				$page = $this->getEditProfilePage();
				if ($page == null)
				{
					Controller::getInstance()->redirect('website', 'Error404');
				}
				HttpController::getInstance()->redirectToUrl(LinkHelper::getDocumentUrl($page));
			}
			else if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' can\'t confirm email for user '.$user->getId(). ' with key '.$key);
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		Controller::getInstance()->redirect('website', 'Error500');
	}
	
	/**
	 * @see f_action_BaseAction::isSecure()
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
	
	/**
	 * @param Request $request
	 * @return users_persistentdocument_frontenduser
	 */
	private function getUserFromRequest($request)
	{
		$userId = $this->getDocumentIdFromRequest($request);
		if ($userId === null || !is_numeric($userId))
		{
			return null;
		}
		try
		{
			$user = DocumentHelper::getDocumentInstance($userId);
			if ($user instanceof users_persistentdocument_frontenduser)
			{
				return $user;
			}
		}
		catch (Exception $e)
		{
			Framework::error(__METHOD__ . ' : ' . $e->getMessage());
		}
		return null;
	}
	
	/**
	 * @return website_persistentdocument_page
	 */
	private function getEditProfilePage()
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		try
		{
			$page = TagService::getInstance()->getDocumentByContextualTag('contextual_website_website_modules_users_edit-profil', $website);
		}
		catch (Exception $e)
		{
			Framework::error(__METHOD__ . ': ' . $e->getMessage());
		}
		return $page;
	}
}