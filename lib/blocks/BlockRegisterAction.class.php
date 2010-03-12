<?php
/**
 * users_BlockRegisterAction
 * @package modules.users.lib.blocks
 */
class users_BlockRegisterAction extends website_BlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		// If there is already a user, redirect to profile edition.
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user !== null)
		{
			$request->setAttribute('user', $user);
			return 'Logged';
		}

		$request->setAttribute('allowAutoLogin', users_ModuleService::getInstance()->allowAutoLogin());
		return website_BlockView::INPUT;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeLogin($request, $response)
	{
		$us = users_UserService::getInstance();
		$login = $this->findParameterValue('login');
		$password = $this->findParameterValue('password');		
		$websiteId = website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getId();
		$user = $us->getIdentifiedFrontendUser($login, $password, $websiteId);
		if ($user !== null)
		{
			$us->authenticateFrontEndUser($user);
			$request->setAttribute('user', $user);
			$autoLogin = $this->findParameterValue('autoLogin');
			if ($autoLogin === 'yes')
			{
				users_ModuleService::getInstance()->setAutoLogin($user);
			}
			return 'Logged';
		}
		else 
		{
			$this->addError(f_Locale::translate('&modules.users.frontoffice.authentication.BadAuthentication;'), 'login-form');
			$request->setAttribute('allowAutoLogin', users_ModuleService::getInstance()->allowAutoLogin());
			return website_BlockView::INPUT;
		}
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeLogout($request, $response)
	{
		users_UserService::getInstance()->authenticateFrontEndUser(null);
		users_ModuleService::getInstance()->unsetAutoLogin();
		$request->setAttribute('allowAutoLogin', users_ModuleService::getInstance()->allowAutoLogin());
		return website_BlockView::INPUT;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @return String
	 */
	public function executeSave($request, $response, users_persistentdocument_websitefrontenduser $user)
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$group = users_WebsitefrontendgroupService::getInstance()->getDefaultByWebsite($website);
		if ($user->getFirstname() === null)
		{
			$user->setFirstname('Anonymous');
		}
		if ($user->getLastname() === null)
		{
			$user->setLastname('Anonymous');
		}
		if ($user->getLogin() === null)
		{
			$user->setLogin($user->getEmail());
		}
		if ($request->hasParameter('password'))
		{
			$password = $request->getParameter('password');
		}
		else 
		{
			$password = $user->getDocumentService()->generatePassword();
		}
		$user->setPasswordmd5(md5($password));
		$user->save($group->getId());
		
		// Email confirmation.
		$user->getDocumentService()->sendEmailConfirmationMessage($user, true, $password);
		
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param users_persistentdocument_websitefrontenduser $user
	 */
	public function validateSaveInput($request, $user)
	{
		$includedFields = array('email');
		if ($this->getConfiguration()->getShowPersonalFields() && $this->getConfiguration()->getRequireNameFields())
		{
			$includedFields[] = 'firstname';
			$includedFields[] = 'lastname';
		}
		$validationRules = BeanUtils::getBeanValidationRules('users_persistentdocument_websitefrontenduser', $includedFields);
		if ($this->getConfiguration()->getShowPasswordFields())
		{
			$validationRules[] = 'password{blank:false}';
			$validationRules[] = 'password_confirm{blank:false}';
		}
		$isOk = $this->processValidationRules($validationRules, $request, $user, 'registration-form');
		
		// Login validation.
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$login = ($request->hasParameter('login')) ? $request->getParameter('login') : $request->getParameter('email');
		if (in_array($login, users_ModuleService::getInstance()->getDisallowedLogins()))
		{
			$this->addError(f_Locale::translate('&modules.users.frontoffice.Login-disallowed;'), 'registration-form');
			$isOk = false;
		}
		else if (users_UserService::getInstance()->getFrontendUserByLogin($login, $website->getId()))
		{
			$this->addError(f_Locale::translate('&modules.users.frontoffice.Login-used;'), 'registration-form');
			$isOk = false;
		}
		
		// Password validation.
		if ($this->getConfiguration()->getShowPasswordFields())
		{
			$password = $request->getParameter('password');
			if ($password !== $request->getParameter('password_confirm'))
			{
				$this->addError(f_Locale::translate('&modules.users.frontoffice.Password-wrong;'), 'registration-form');
				$isOk = false;
			}
			$property = new validation_Property(f_Locale::translate('&modules.users.document.websitefrontenduser.password;'), $password);
			$passwordValidator = new validation_PasswordValidator();
			$errors = new validation_Errors();
			if (!$passwordValidator->validate($property, $errors))
			{
				$this->addError($errors[0], 'registration-form');
				$isOk = false;
			}
		}
		
		return $isOk;
	}
}