<?php
/**
 * users_patch_0360
 * @package modules.users
 */
class users_patch_0360 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('notifications.xml');
	}
}