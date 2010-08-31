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
	 * @return String
	 */
	function execute($request, $response)
    {
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		$request->setAttribute('user', $user);
		
		return website_BlockView::INPUT;
    }
    
    public function saveNeedTransaction()
    {
    	return true;
    }
    
    /**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param users_persistentdocument_websitefrontenduser $user
	 * @return String
	 */
	public function executeSave($request, $response, users_persistentdocument_websitefrontenduser $user)
	{
		if ($user->getLogin() === null)
		{
			$user->setLogin($user->getEmail());
		}
		$user->save();
		
		//TODO: Email confirmation.
		//$user->getDocumentService()->sendEmailConfirmationMessage($user, false);
		
		$this->addMessage(f_Locale::translate('&modules.users.frontoffice.Informations-updated;'));
		
		return website_BlockView::INPUT;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param users_persistentdocument_websitefrontenduser $user
	 */
	public function validateSaveInput($request, $user)
	{
		$includedFields = array('email');
		$includedFields[] = 'firstname';
		$includedFields[] = 'lastname';
		$validationRules = BeanUtils::getBeanValidationRules('users_persistentdocument_websitefrontenduser', $includedFields);
		$isOk = $this->processValidationRules($validationRules, $request, $user);
		
		// Login validation.
		if ($user->isPropertyModified('login'))
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$login = ($request->hasParameter('login')) ? $request->getParameter('login') : $request->getParameter('email');
			if (in_array($login, users_ModuleService::getInstance()->getDisallowedLogins()))
			{
				$this->addError(f_Locale::translate('&modules.users.frontoffice.Login-disallowed;'));
				$isOk = false;
			}
			else if (users_UserService::getInstance()->getFrontendUserByLogin($login, $website->getId()))
			{
				$this->addError(f_Locale::translate('&modules.users.frontoffice.Login-used;'));
				$isOk = false;
			}
		}
		return $isOk;
	}
}