<?php
/**
 * users_ConfirmEmailAction
 * @package modules.users.actions
 */
class users_ConfirmEmailAction extends change_Action
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	protected function _execute($context, $request)
	{	
		$user = $this->getUserFromRequest($request);
		$key = $request->getParameter('key');
		if ($key === null || $user === null)
		{
			change_Controller::getInstance()->redirect('website', 'Error500');
		}
		try
		{
			if ($user->getDocumentService()->confirmEmail($user, $key))
			{
				users_UserService::getInstance()->authenticate($user);
				$page = $this->getEditProfilePage();
				if ($page == null)
				{
					change_Controller::getInstance()->redirect('website', 'Error404');
				}
				change_Controller::getInstance()->redirectToUrl(LinkHelper::getDocumentUrl($page));
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
		change_Controller::getInstance()->redirect('website', 'Error500');
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
	 * @param change_Request $request
	 * @return users_persistentdocument_user
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
			if ($user instanceof users_persistentdocument_user)
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
		$website = website_WebsiteService::getInstance()->getCurrentWebsite();
 		return TagService::getInstance()->getDocumentByContextualTag('contextual_website_website_modules_users_edit-profil', $website, false);
	}
}