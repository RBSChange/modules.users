<?php
/**
 * @package modules.users
 */
class users_RefreshDynamicgroupsTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		foreach (users_DynamicgroupService::getInstance()->getToRefresh() as $group)
		{
			$group->getDocumentService()->refresh($group);
		}
	}
}