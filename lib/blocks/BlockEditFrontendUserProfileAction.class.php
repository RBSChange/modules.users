<?php
/**
 * users_BlockEditFrontendUserProfileAction
 * @package modules.users.lib.blocks
 */
class users_BlockEditFrontendUserProfileAction extends website_BlockAction
{
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
		$request->setAttribute('user', $user);

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
	 * @param users_persistentdocument_user $user
	 * @return string
	 */
	public function executeSave($request, $response, users_persistentdocument_user $user)
	{
		if ($user->getLogin() === null)
		{
			$user->setLogin($user->getEmail());
		}
		$user->save();
		$request->setAttribute('user', $user);

		$this->addMessage(LocaleService::getInstance()->trans('m.users.frontoffice.informations-updated', array('ucf', 'html')));

		return website_BlockView::INPUT;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param users_persistentdocument_user $user
	 */
	public function validateSaveInput($request, $user)
	{
		$includedFields = array('email');
		$validationRules = BeanUtils::getBeanValidationRules('users_persistentdocument_user', $includedFields);
		$isOk = $this->processValidationRules($validationRules, $request, $user);

		// Login validation.
		if ($user->isPropertyModified('login'))
		{
			$login = ($request->hasParameter('login')) ? $request->getParameter('login') : $request->getParameter('email');
			if (in_array($login, users_ModuleService::getInstance()->getDisallowedLogins()))
			{
				$this->addError(LocaleService::getInstance()->trans('m.users.frontoffice.login-disallowed', array('ucf', 'html')));
				$isOk = false;
			}
			else if (!users_UserService::getInstance()->validateUserLogin($login, $user))
			{
				$this->addError(LocaleService::getInstance()->trans('m.users.frontoffice.login-used', array('ucf', 'html')));
				$isOk = false;
			}
		}
		return $isOk;
	}
}