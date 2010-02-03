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
}