<?php
/**
 * users_patch_0303
 * @package modules.users
 */
class users_patch_0303 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		f_util_System::execChangeCommand('generate-database', array('users'));
		
		$this->executeLocalXmlScript('task.xml');
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
		return '0303';
	}
}