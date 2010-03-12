<?php
/**
 * users_ModuleService
 * @package modules.users.lib.services
 */
class users_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var users_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return users_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	public function getVirtualParentForBackoffice($document)
	{
		if ($document instanceof users_persistentdocument_user)
		{
			return f_util_ArrayUtils::firstElement($document->getGroupsArray());
		}
		return null;
	}
	
	/**
	 * @return String[]
	 */
	public function getDisallowedLogins()
	{
		return array('wwwadmin', 'www-admin', 'www.admin', 'root', 'admin', 'administrator', 'administrateur');
		// TODO: Module preference? Websitefrontendgroup preference?
	}
	
	// Auto-login handling.
	
	/**
	 * @return Boolean
	 */
	public function allowAutoLogin()
	{
		return (f_util_ClassUtils::methodExists('controller_ChangeController', 'allowAutoLogin') && controller_ChangeController::allowAutoLogin() === true);
	}
	
	/**
	 * @return Boolean
	 */
	public function setAutoLogin($user)
	{
		if ($this->allowAutoLogin())
		{
			setcookie(controller_ChangeController::AUTO_LOGIN_COOKIE . '[login]', $user->getLogin(), time() + 365*24*3600, '/');
      		setcookie(controller_ChangeController::AUTO_LOGIN_COOKIE . '[passwd]', sha1($user->getPasswordmd5()), time() + 365*24*3600, '/');
		}
	}
	
	/**
	 * @return Boolean
	 */
	public function unsetAutoLogin($user)
	{
		if ($this->allowAutoLogin())
		{
			setcookie(controller_ChangeController::AUTO_LOGIN_COOKIE . '[login]', '', time(), '/');
      		setcookie(controller_ChangeController::AUTO_LOGIN_COOKIE . '[passwd]', '', time(), '/');
		}
	}
}