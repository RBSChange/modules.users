<?php
/**
 * users_patch_0350
 * @package modules.users
 */
class users_patch_0350 extends patch_BasePatch
{

 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/users/persistentdocument/websitefrontendgroup.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'users', 'websitefrontendgroup');
		$newProp = $newModel->getPropertyByName('linkedwebsites');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('users', 'websitefrontendgroup', $newProp);
		$this->execChangeCommand('compile-db-schema');
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'users';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0350';
	}
}