<?php
class users_SetUserModulePermissionAction extends change_JSONAction
{
	/**
	 * @return Boolean
	 */
	protected function isDocumentAction()
	{
		return false;
	}
	
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$accessor = $this->getDocumentInstanceFromRequest($request);
		
		// 0 => 'ads,Admin,1547,2'
		$acl = $request->getParameter('acl', array());
		
		$ps = f_permission_PermissionService::getInstance();
		$tm = $this->getTransactionManager();
		$modifiedRoles = array();
		foreach ($acl as $data) 
		{
			list($moduleName, $roleName, $rootFolderId, $state) = explode(',', $data);
			$fullQualifiedRoleName = 'modules_' . $moduleName . '.' . $roleName;
			$modifiedRoles[] = $fullQualifiedRoleName;
			$deleteAcl = ($state == '1');
			try 
			{
				$tm->beginTransaction();
				if ($deleteAcl)
				{
					if ($accessor instanceof users_persistentdocument_group)
					{
						$ps->removeGroupPermission($accessor, $fullQualifiedRoleName, array($rootFolderId));
					}
					else
					{
						$ps->removeUserPermission($accessor, $fullQualifiedRoleName, array($rootFolderId));
					}
				}
				else
				{
					if ($accessor instanceof users_persistentdocument_group) 
					{
						$ps->addRoleToGroup($accessor, $fullQualifiedRoleName, array($rootFolderId));
					}
					else
					{
						$ps->addRoleToUser($accessor, $fullQualifiedRoleName, array($rootFolderId));	
					}
				}
				$tm->commit();
			} 
			catch (Exception $e)
			{
				$tm->rollBack($e);	
			}
		}
		$eventParam = array('nodeId' => null, 'updatedRoles' => $modifiedRoles, 'module' => null);
		$ps->dispatchPermissionsUpdatedEvent($eventParam);
		return $this->sendJSON($eventParam);
	}
}