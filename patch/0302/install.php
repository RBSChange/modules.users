<?php
/**
 * users_patch_0302
 * @package modules.users
 */
class users_patch_0302 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		f_util_System::execChangeCommand('compile-roles');
		f_util_System::execChangeCommand('compile-permissions');
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
		return '0302';
	}
}