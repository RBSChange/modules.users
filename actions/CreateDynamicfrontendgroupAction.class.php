<?php
/**
 * @author intportg
 * @package modules.users
 */
class users_CreateDynamicfrontendgroupAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$doc = DocumentHelper::getDocumentInstance($request->getParameter('id'));
		$service = users_DynamicfrontendgroupService::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try 
		{
			$tm->beginTransaction();
			$frontendgroup = $service->getNewDocumentInstance();
			$frontendgroup->setLabel($doc->getLabel());
			$frontendgroup->setClassName($request->getParameter('className'));
			$frontendgroup->setParameter('referenceId', $doc->getId());
			$frontendgroup->save(ModuleService::getInstance()->getSystemFolderId('users', $request->getParameter('forModule')));
			if (f_util_ClassUtils::methodExists($doc->getDocumentService(), 'onDynamicfrontendgroupCreated'))
			{
				$doc->getDocumentService()->onDynamicfrontendgroupCreated($doc, $frontendgroup);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
		$service->refresh($frontendgroup);
		return $this->sendJSON(array('id' => $frontendgroup->getId()));
	}
}