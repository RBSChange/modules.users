<?php
/**
 * @package modules.users
 * @method users_ModuleService getInstance()
 */
class users_ModuleService extends ModuleBaseService
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	public function getVirtualParentForBackoffice($document)
	{
		if ($document instanceof users_persistentdocument_user)
		{
			if (count($document->getGroupsCount()))
			{
				return f_util_ArrayUtils::firstElement($document->getGroupsArray());
			}
			return users_NogroupuserfolderService::getInstance()->getNoGroupUserFolder();
		}
		return null;
	}
	
	/**
	 * @return string[]
	 */
	public function getDisallowedLogins()
	{
		return array('wwwadmin', 'www-admin', 'www.admin', 'root', 'admin', 'administrator', 'administrateur');
	}
	
	// Auto-login handling.
	
	/**
	 * @return boolean
	 */
	public function allowAutoLogin()
	{
		return (change_Controller::getInstance()->allowAutoLogin() === true);
	}
	
	/**
	 * @return boolean
	 */
	public function setAutoLogin($user)
	{
		if ($this->allowAutoLogin())
		{
			setcookie(change_Controller::AUTO_LOGIN_COOKIE . '[login]', $user->getLogin(), time() + 365*24*3600, '/');
	  		setcookie(change_Controller::AUTO_LOGIN_COOKIE . '[passwd]', sha1($user->getPasswordmd5()), time() + 365*24*3600, '/');
		}
	}
	
	/**
	 * @return boolean
	 */
	public function unsetAutoLogin()
	{
		if ($this->allowAutoLogin())
		{
			setcookie(change_Controller::AUTO_LOGIN_COOKIE . '[login]', '', time() - 3600, '/');
			setcookie(change_Controller::AUTO_LOGIN_COOKIE . '[passwd]', '', time() - 3600, '/');
		}
	}
	
	// Default structure importation.
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	public function getStructureInitializationAttributes($container, $attributes, $script)
	{
		switch ($script)
		{
			case 'memberDefaultStructure' :
				return $this->getUsersStructureInitializationAttributes($container, $attributes, $script);
			
			default:
				throw new BaseException('Unknown structure initialization script: '.$script, 'm.website.bo.actions.unknown-structure-initialization-script', array('script' => $script));
		}
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	public function getUsersStructureInitializationAttributes($container, $attributes, $script)
	{
		// Check container.
		if (!$container instanceof website_persistentdocument_website && !$container instanceof website_persistentdocument_topic)
		{
			throw new BaseException('Invalid website or topic', 'm.website.bo.general.invalid-website-or-topic');
		}
		
		if ($container instanceof website_persistentdocument_website)
		{
			$websiteId = $container->getId();
		}
		else 
		{
			$websiteId = $container->getDocumentService()->getWebsiteId($container);
		}
		
		$ts = TagService::getInstance();
		$website = DocumentHelper::getDocumentInstance($websiteId, 'modules_website/website');
		if ($ts->hasDocumentByContextualTag('contextual_website_website_modules_users_userlist', $website) || 
			$ts->hasDocumentByContextualTag('contextual_website_website_modules_users_user', $website))
		{
			throw new BaseException('Some pages are already initialized', 'm.website.bo.actions.some-pages-already-initialized');
		}
		
		// Set atrtibutes.
		$attributes['byDocumentId'] = $container->getId();
		$attributes['type'] = $container->getPersistentModel()->getName();
		return $attributes;
	}
}