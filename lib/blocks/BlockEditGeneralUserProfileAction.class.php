<?php
/**
 * users_BlockEditGeneralUserProfileAction
 * @package modules.users.lib.blocks
 */
class users_BlockEditGeneralUserProfileAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @return string
	 */
	protected function initRequest($request)
	{
		$rc = RequestContext::getInstance();
		$lang = $rc->getLang();
		$request->setAttribute('currentDateFormat', $rc->getDateFormat($lang));
		$request->setAttribute('currentDatetimeFormat', $rc->getDateTimeFormat($lang));
	}
	
	/**
	 * @see website_BlockAction::execute()
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::INPUT;
		}

		$user = users_UserService::getInstance()->getCurrentUser();
		$profile = users_UsersprofileService::getInstance()->getByAccessorId($user->getId());
		if ($profile === null)
		{
			$profile = users_UsersprofileService::getInstance()->getNewDocumentInstance();
			$profile->setAccessor($user);
			$profile->setTimezone(DEFAULT_TIMEZONE);
		}
		$request->setAttribute('profile', $profile);
		$this->initRequest($request);
		return website_BlockView::INPUT;
	}

	/**
	 * @return boolean
	 */
	public function saveNeedTransaction()
	{
		return true;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param users_persistentdocument_usersprofile $profile
	 * @return string
	 */
	public function executeSave($request, $response, users_persistentdocument_usersprofile $profile)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($profile->isNew())
		{
			$profile->setAccessor($user);
		}
		elseif ($user->getId() != $profile->getAccessorId()) 
		{
			throw new BaseException('Not your profile!', 'm.users.fo.not-your-profile');
		}
		$profile->save();
		$request->setAttribute('profile', $profile);
		RequestContext::getInstance()->resetProfile();
		users_ProfileService::getInstance()->initCurrent(false);
		$this->addMessage(LocaleService::getInstance()->trans('m.users.frontoffice.informations-updated', array('ucf', 'html')));
		$this->initRequest($request);
		return website_BlockView::INPUT;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param users_persistentdocument_usersprofile $profile
	 */
	public function validateSaveInput($request, $profile)
	{
		$user = users_UserService::getInstance()->getCurrentUser();
		$profile->setAccessor($user);
		
		$validationRules = BeanUtils::getBeanValidationRules('users_persistentdocument_usersprofile');
		return $this->processValidationRules($validationRules, $request, $profile);
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	public function onValidateInputFailed($request)
	{
		$this->initRequest($request);
	}
}