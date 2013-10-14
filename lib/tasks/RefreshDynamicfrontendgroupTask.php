<?php
/**
 * @author intportg
 * @package modules.users
 */
class users_RefreshDynamicfrontendgroupTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		if (!$this->hasParameter('groupId'))
		{
			Framework::error(__METHOD__ . ': No group id to refresh!');
			return;
		}
		
		$groupId = intval($this->getParameter('groupId'));
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$modelName = $pp->getDocumentModelName($groupId);
		if (!$modelName)
		{
			Framework::error(__METHOD__ . ': The given document (id = ' . $groupId . ') does not exist any more!');
			return;
		}
		
		$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		if (!$model->isModelCompatible('modules_users/dynamicfrontendgroup'))
		{
			Framework::error(__METHOD__ . ': The given document (id = ' . $groupId . ') is not a dynamic frontend group (modelName = ' . $modelName . '!');
			return;
		}
		
		$errors = array();
		$group = users_persistentdocument_dynamicfrontendgroup::getInstanceById($groupId);
		$service = $group->getDocumentService();
		$feeder = $service->getFeeder($group);
		
		$oldIds = $service->getUserIds($group);
		$this->plannedTask->ping();
		
		$newIds = $feeder->getUserIds($group);
		$this->plannedTask->ping();
		
		// Apply removals.
		$subscriberIdArray = array_diff($oldIds, $newIds);
		$batchPath = $this->getBatchRemoverPath();
		foreach (array_chunk($subscriberIdArray, 500) as $batch)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execScript($batchPath, array_merge(array($groupId), $batch));
			// Log fatal errors...
			if ($result != 'OK')
			{
				$errors[] = $result;
			}
		}
		
		// Apply addings.
		$relatedIdArray = array_diff($newIds, $oldIds);
		$batchPath = $this->getBatchAdderPath();
		foreach (array_chunk($relatedIdArray, 500) as $batch)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execScript($batchPath, array_merge(array($groupId), $batch));
			// Log fatal errors...
			if ($result != 'OK')
			{
				$errors[] = $result;
			}
		}
		
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
		
		// Set the refreshing flag to false.
		$group->setRefreshing(false);
		$group->save();
	}
	
	/**
	 * @return String
	 */
	private function getBatchRemoverPath()
	{
		return f_util_FileUtils::buildRelativePath('modules', 'users', 'lib', 'bin', 'batchUserRemover.php');
	}
	
	/**
	 * @return String
	 */
	private function getBatchAdderPath()
	{
		return f_util_FileUtils::buildRelativePath('modules', 'users', 'lib', 'bin', 'batchUserAdder.php');
	}
}
