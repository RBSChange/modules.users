<?php
/**
 * users_patch_0352
 * @package modules.users
 */
class users_patch_0352 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('compile-locales', array('users'));
		$this->executeLocalXmlScript('init.xml');
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
		return '0352';
	}
}