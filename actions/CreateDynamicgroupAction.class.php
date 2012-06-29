<?php
/**
 * @package modules.users
 */
class users_CreateDynamicgroupAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$doc = DocumentHelper::getDocumentInstance($request->getParameter('id'));
		$service = users_DynamicgroupService::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try 
		{
			$tm->beginTransaction();
			$group = $service->getNewDocumentInstance();
			$group->setLabel($doc->getLabel());
			$group->setClassName($request->getParameter('className'));
			$group->setParameter('referenceId', $doc->getId());
			$group->save(ModuleService::getInstance()->getSystemFolderId('users', $request->getParameter('forModule')));
			if (f_util_ClassUtils::methodExists($doc->getDocumentService(), 'onDynamicgroupCreated'))
			{
				$doc->getDocumentService()->onDynamicgroupCreated($doc, $group);
			}
			//Deprected and removed on next version
			elseif (f_util_ClassUtils::methodExists($doc->getDocumentService(), 'onDynamicfrontendgroupCreated'))
			{
				$doc->getDocumentService()->onDynamicfrontendgroupCreated($doc, $group);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
		$service->refresh($group);
		return $this->sendJSON(array('id' => $group->getId()));
	}
}