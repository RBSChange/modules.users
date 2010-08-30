<?php
/**
 * @author intportg
 * @package modules.users
 */
class users_RefreshDynamicfrontendgroupsTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		chdir(WEBEDIT_HOME);
		foreach (users_DynamicfrontendgroupService::getInstance()->getToRefresh() as $group)
		{
			$group->getDocumentService()->refresh($group);
		}
	}
}