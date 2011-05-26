<?php
/**
 * @author intportg
 * @package modules.users
 */
abstract class users_FrontendgroupFeederBaseService extends BaseService
{
	/**
	 * @param users_persistentdocument_dynamicfrontendgroup $group
	 */
	public function refreshUsers($group)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
			
			// Set the refreshing flag to true.
			$group->setRefreshing(true);
			$group->save();
			
			// Create the planed task.
			$refreshListTask = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
			$refreshListTask->setSystemtaskclassname('users_RefreshDynamicfrontendgroupTask');
			$refreshListTask->setLabel(__METHOD__);
			$refreshListTask->setParameters(serialize(array('groupId' => $group->getId())));
			$refreshListTask->setUniqueExecutiondate(date_Calendar::getInstance());
			$refreshListTask->save();
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
	}
	
	/**
	 * @param users_persistentdocument_dynamicfrontendgroup $group
	 */
	public abstract function getUserIds($group);
}