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
		chdir(WEBEDIT_HOME);
		if (!$this->hasParameter('groupId'))
		{
			Framework::error(__METHOD__ . ': No group id to refresh!');	
			return;
		}
		$groupId = intval($this->getParameter('groupId'));
		$group = DocumentHelper::getDocumentInstance($groupId);
		if (!($group instanceof users_persistentdocument_dynamicfrontendgroup))
		{
			Framework::error(__METHOD__ . ': The given document (id = '.$groupId.') is not a dynamic frontend group!');	
			return;
		}
		
		$service = $group->getDocumentService();
		$feeder = $service->getFeeder($group);
		
		$oldIds = $service->getUserIds($group);
		$newIds = $feeder->getUserIds($group);
		
		// Apply removals.
		$subscriberIdArray = array_diff($oldIds, $newIds);
		$batchPath = $this->getBatchRemoverPath();
		foreach (array_chunk($subscriberIdArray, 500) as $batch)
		{
			echo f_util_System::execHTTPScript($batchPath, $batch);
		}
		
		// Apply addings.
		$relatedIdArray = array_diff($newIds, $oldIds);
		$batchPath = $this->getBatchAdderPath();
		foreach (array_chunk($relatedIdArray, 500) as $batch)
		{
			echo f_util_System::execHTTPScript($batchPath, array_merge(array($groupId), $batch));
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