<?php
class users_BlockAdminauthenticationAction extends website_BlockAction
{
	/**
	 * @see users_LoginInfoLoadHandler
	 * @var String
	 */
	const LOGIN_INFO_KEY = "users_Adminauthentication";
	
	/**
	 * @see f_mvc_Action::execute()
	 * @see users_LoginInfoLoadHandler
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		if ($user !== null)
		{
			$request->setAttribute("user", $user);
			return "Logged";
		}
		return $this->getLoginInputViewName();
	}

	/**
	 * @return String
	 */
	function getLoginInputViewName()
	{
		return "Form";
	}

	/**
	 * @return Boolean
	 */
	function validateLoginInput($request)
	{
		$login = $request->getParameter("login");
		$password = $request->getParameter("password");
		if (f_util_StringUtils::isEmpty($login) || f_util_StringUtils::isEmpty($password))
		{
			$this->addError(f_Locale::translate('&modules.users.errors.Invalid-login-or-password;'));
			return false;
		}
		return true;
	}

	/**
	 * @see f_mvc_Action::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function executeLogin($request, $response)
	{
		$login = $request->getParameter("login");
		$password = $request->getParameter("password");
				
		$us = users_UserService::getInstance();
		$user = $us->getIdentifiedBackendUser($login, $password);
		if ($user !== null)
		{
			$us->authenticateBackEndUser($user);
			$projectId = defined("PROJECT_ID") ? PROJECT_ID : PROFILE;
			$_SESSION['ChromeBaseUri'] = "rbschange/content/ext/" . $projectId;
				
			UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry("Login", $user, array(), "users");
			$request->setAttribute("user", $user);
			if ($request->getParameter("rememberMe") == "true")
			{
				$loginInfo = JsonService::getInstance()->encode(array("login" => $login, "password" => $password));
				$this->getHTTPRequest()->setCookie(self::LOGIN_INFO_KEY, $loginInfo);
			}
			return "Logged";
		}
		
		$this->addError(f_Locale::translate('&modules.users.errors.Invalid-authentification;'));
		return $this->getLoginInputViewName();
	}

	/**
	 * @see f_mvc_Action::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function executeLogout($request, $response)
	{
		$us = users_UserService::getInstance();
		$user = $us->getCurrentBackEndUser();
		if ($user !== null)
		{
			$us->authenticateBackEndUser(null);
			UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry("Logout", $user, array(), "users");
		}
		$this->addMessage(f_Locale::translate("&modules.users.frontoffice.adminauthentication.logout-success;"));
		return $this->getLoginInputViewName();
	}
	
	/**
	 * @see f_mvc_Action::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function executeLostpasswordform($request, $response)
	{
		return $this->getLostpasswordInputViewName();
	}
	
	/**
	 * @see f_mvc_Action::execute()
	 * @param f_mvc_Request $request
	 */
	function validateLostpasswordInput($request)
	{
		$email = $request->getParameter("login");
		if (f_util_StringUtils::isEmpty($email))
		{
			$this->addError(f_Locale::translate('&framework.validation.validator.Blank.message;', array("field" => f_Locale::translate("&modules.users.frontoffice.adminauthentication.login;"))));
			return false;
		}
		return true;
	}
	
	/**
	 * @return String
	 */
	function getLostpasswordInputViewName()
	{
		return "LostPasswordForm";
	}
	
	/**
	 * @see f_mvc_Action::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function executeLostpassword($request, $response)
	{
		$login = $request->getParameter("login");
		$us = users_BackenduserService::getInstance();
		$user = $us->getBackEndUserByLogin($login);
		if ($user === null)
		{
			$this->addError(f_Locale::translate('&modules.users.frontoffice.adminauthentification.lostpassword-nouser;', array("login" => $login)));
			return $this->getLostpasswordInputViewName();
		}
		$us->prepareNewPassword($user->getLogin());
		return "LostPasswordSuccess";
	}
}