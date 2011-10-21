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
		$request->setAttribute('allowAutoLogin', users_ModuleService::getInstance()->allowAutoLogin());
		if ($this->isInBackoffice())
		{
			return website_BlockView::INPUT;
		}
		$request->setAttribute('backUrl', LinkHelper::getDocumentUrl($this->getContext()->getPersistentPage()));
			
		// If there is already a user, redirect to profile edition.
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user !== null)
		{
			$request->setAttribute('user', $user);	
			return 'Logged';
		}
		$storageId = $this->getContext()->getId() . '_' . $this->getBlockId();
		$request->setAttribute('storageId', $storageId);
		
		$data = change_Controller::getInstance()->getStorage()->read($storageId);
		if (is_array($data))
		{
			foreach ($data as $name => $value) 
			{
				$request->setAttribute($name, $value);
			}
			change_Controller::getInstance()->getStorage()->remove($storageId);
		}

		$request->setAttribute('authenticateUrl', LinkHelper::getActionUrl('users', 'Authenticate', array('location'=> $request->getAttribute('backUrl'))));
		return website_BlockView::INPUT;
	}
		
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param users_persistentdocument_user $user
	 * @return String
	 */
	public function executeSave($request, $response, users_persistentdocument_user $user)
	{
		$us = users_UserService::getInstance();
		$website = website_WebsiteService::getInstance()->getCurrentWebsite();
		$user->addGroups($website->getGroup());

		if ($request->hasParameter('password'))
		{
			$password = $request->getParameter('password');
		}
		else 
		{
			$password = $us->generatePassword();
		}	
		$user->setPassword(null);	
		$user->setPasswordmd5($us->encodePassword($password));
		$user->save();
		
		// Email confirmation.
		$us->sendEmailConfirmationMessage($user, true, $password);
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param users_persistentdocument_user $user
	 */
	public function validateSaveInput($request, $user)
	{
		$validationRules = BeanUtils::getBeanValidationRules('users_persistentdocument_user', array('email'));
		if ($this->getConfiguration()->getShowPasswordFields())
		{
			$validationRules[] = 'password{blank:false}';
			$validationRules[] = 'password_confirm{blank:false}';
		}
		$isOk = $this->processValidationRules($validationRules, $request, $user, 'registration-form');
		
		// Login validation.
		$website = website_WebsiteService::getInstance()->getCurrentWebsite();
		$group = $website->getGroup();
		$user->addGroups($group);
		$ls = LocaleService::getInstance();
		$login = ($request->hasNonEmptyParameter('login')) ? $request->getParameter('login') : $request->getParameter('email');
		
		if (in_array($login, users_ModuleService::getInstance()->getDisallowedLogins()))
		{
			$this->addError($ls->transFO('m.users.frontoffice.login-disallowed', array('ucf')), 'registration-form');
			$isOk = false;
		}
		else if (!users_UserService::getInstance()->validateUserLogin($login, $user))
		{
			$this->addError($ls->transFO('m.users.frontoffice.login-used', array('ucf')), 'registration-form');
			$isOk = false;
		}
		
		// Password validation.
		if ($this->getConfiguration()->getShowPasswordFields())
		{
			$password = $request->getParameter('password');
			if ($password !== $request->getParameter('password_confirm'))
			{
				$this->addError($ls->transFO('m.users.frontoffice.password-wrong', array('ucf')), 'registration-form');
				$isOk = false;
			}
			$property = new validation_Property($ls->transFO('users.document.user.password', array('ucf')), $password);	
			$passwordValidator = new validation_PasswordValidator($user);
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