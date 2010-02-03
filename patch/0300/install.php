<?php
/**
 * @author intportg
 * @package modules.users
 */
class users_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		foreach (users_WebsitefrontendgroupService::getInstance()->createQuery()->find() as $group)
		{
			$group->setIsdefault(true);
			$group->save();
		}
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'users';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
}