<?php
/**
 * users_BlockRegisterAction
 * @package modules.users.lib.blocks
 */
class users_BlockRegisterAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			$this->prepareInputView($request);
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
		
		$this->prepareInputView($request);
		return website_BlockView::INPUT;
	}
		
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param users_persistentdocument_user $user
	 * @return string
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
		$ls = LocaleService::getInstance();
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
		$login = ($request->hasNonEmptyParameter('login')) ? $request->getParameter('login') : $request->getParameter('email');
		
		if (in_array($login, users_ModuleService::getInstance()->getDisallowedLogins()))
		{
			$this->addError($ls->trans('m.users.frontoffice.login-disallowed', array('ucf')), 'registration-form');
			$isOk = false;
		}
		else if (!users_UserService::getInstance()->validateUserLogin($login, $user))
		{
			$this->addError($ls->trans('m.users.frontoffice.login-used', array('ucf')), 'registration-form');
			$isOk = false;
		}
		
		// Password validation.
		if ($this->getConfiguration()->getShowPasswordFields())
		{
			$password = $request->getParameter('password');
			$property = new validation_Property($ls->trans('m.users.document.user.password', array('ucf')), $password);
			$passwordValidator = new validation_PasswordValidator($user);
			$errors = new validation_Errors();
			if (!$passwordValidator->validate($property, $errors))
			{
				$this->addError($errors[0], 'registration-form');
				$isOk = false;
			}
			
			if ($password !== $request->getParameter('password_confirm'))
			{
				$this->addError($ls->trans('m.users.frontoffice.password-not-confirmed', array('ucf')), 'registration-form');
				$isOk = false;
			}
		}
		return $isOk;
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	public function onValidateInputFailed($request)
	{
		$this->prepareInputView($request);
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	protected function prepareInputView($request)
	{
		$request->setAttribute('allowAutoLogin', users_ModuleService::getInstance()->allowAutoLogin());
	}
}