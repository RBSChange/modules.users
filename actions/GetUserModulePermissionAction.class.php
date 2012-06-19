<?php
class users_GetUserModulePermissionAction extends change_JSONAction
{
	/**
	 * @return boolean
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
		$ls = LocaleService::getInstance();
		$currentUser = users_UserService::getInstance()->getAutenticatedUser();
		$accessor = $this->getDocumentInstanceFromRequest($request);
		if ($accessor instanceof users_persistentdocument_group)
		{
			$type = 'group';
			$label = $ls->trans('m.users.bo.dialog.group-title', array('ucf'), array('name' => $accessor->getLabel()));
		}
		else
		{
			$type = 'user';
			$label = $ls->trans('m.users.bo.dialog.user-title', array('ucf'), array('name' => $accessor->getLabel()));
		}
		$documentIds = array();
		$result = array();
		$result['user'] = array('id' => $accessor->getId(), 'type' => $type, 'label' => $label);
		$result['roles'] = array();
		
		$allowedRoles = explode(',' , Framework::getConfigurationValue('modules/users/allowedRoles', 'Admin,Writer,Validator,Translator,Guest,User'));
		foreach ($allowedRoles as $roleName) 
		{
			$result['roles'][$roleName]  = array(
				'name' => $ls->trans('m.users.document.permission.'. strtolower($roleName), array('ucf')), 
				'used' => 0);
		}	
		$modules = ModuleService::getInstance()->getPackageNames();
		foreach ($modules as $packageName) 
		{
			$moduleName = ModuleService::getInstance()->getShortModuleName($packageName);
			
			// Check si des roles sont defini sur ce module
			$rs = change_PermissionService::getRoleServiceByModuleName($moduleName);
			if ($rs === null) 
			{
				continue;
			}
			
			$rootfolderid = ModuleService::getInstance()->getRootFolderId($moduleName);
			
			$addRolesDefinition = false;
			
			// Permission d'affecter les rôle
			if (change_PermissionService::getInstance()->hasPermission($currentUser, $packageName . '.LoadPermissions.rootfolder', $rootfolderid))
			{
				$addRolesDefinition = true;
				$documentIds[$rootfolderid] = $moduleName;
				
				$result['modules'][$moduleName]['rootfolderid'] = ModuleService::getInstance()->getRootFolderId($moduleName);
				$result['modules'][$moduleName]['name'] = $ls->trans('m.'. $moduleName.'.bo.general.module-name', array('ucf'));
			}
			
			$roles = array();
					
			foreach ($rs->getRoles() as $qualifiefRoleName) 
			{
				list(, $roleName) = explode('.', $qualifiefRoleName);
				if (!in_array($roleName, $allowedRoles)) {continue;}
				$result['roles'][$roleName]['used'] += 1;
				$roles[$roleName] = 1;
			}
			
			if ($addRolesDefinition)
			{
				$result['modules'][$moduleName]['roles'] = $roles;
			}
		}
		
		uasort($result['modules'], array(__CLASS__, "sortModule"));
		
		if ($type == 'group')
		{
			$query = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl')
				->add(Restrictions::eq('group.id', $accessor->getId()));
		}
		else
		{
			$query = $this->getPersistentProvider()->createQuery('modules_generic/userAcl')
				->add(Restrictions::eq('user.id', $accessor->getId()));
		}
		$query->add(Restrictions::in('documentId', array_keys($documentIds)));
		foreach ($query->find() as $acl) 
		{
			list($packageName, $roleName) = explode('.', $acl->getRole());
			$moduleName = str_replace('modules_', '', $packageName);
			$result['modules'][$moduleName]['roles'][$roleName] = 2;
		}
		
		return $this->sendJSON($result);
	}
	
	public static function sortModule($a, $b)
	{
		$al = strtolower($a['name']);
		$bl = strtolower($b['name']);
		if ($al == $bl) {
			return 0;
		}
		return ($al > $bl) ? +1 : -1;	
	}
}