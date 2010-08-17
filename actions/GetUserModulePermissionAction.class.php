<?php

class users_GetUserModulePermissionAction extends f_action_BaseJSONAction
{
	
	/**
	 * @see f_action_BaseAction::suffixSecureActionByDocument()
	 *
	 * @return Boolean
	 */
	protected function suffixSecureActionByDocument()
	{
		return false;
	}
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$currentUser = users_BackenduserService::getInstance()->getCurrentBackEndUser();
		
		$accessor = $this->getDocumentInstanceFromRequest($request);
		if ($accessor instanceof users_persistentdocument_group)
		{
			$type = 'group';
			$label = f_Locale::translateUI('&modules.users.bo.dialog.Group-title;', array('name' => $accessor->getLabel()));
		}
		else
		{
			$type = 'user';
			$label = f_Locale::translateUI('&modules.users.bo.dialog.User-title;', array('name' => $accessor->getFullName()));
		}
		$documentIds = array();
		$result = array();
		$result['user'] = array('id' => $accessor->getId(), 'type' => $type, 'label' => $label);
		$result['roles'] = array();
		
		$modules = ModuleService::getInstance()->getModules();
		foreach ($modules as $packageName) 
		{
			
			$moduleName = str_replace('modules_', '', $packageName);
			
			//Check si des roles sont defini sur ce module
			$rs = f_permission_PermissionService::getRoleServiceByModuleName($moduleName);
			if ($rs === null) 
			{
				continue;
			}
			
			$rootfolderid = ModuleService::getInstance()->getRootFolderId($moduleName);
			
			$addRolesDefinition = false;
			//Permission d'affecter les rôle
			if (f_permission_PermissionService::getInstance()->hasPermission($currentUser, $packageName . '.LoadPermissions.rootfolder', $rootfolderid))
			{
				$addRolesDefinition = true;
				$documentIds[$rootfolderid] = $moduleName;
				
				$result['modules'][$moduleName]['rootfolderid'] = ModuleService::getInstance()->getRootFolderId($moduleName);
				$result['modules'][$moduleName]['name'] = f_Locale::translateUI('&modules.'. $moduleName.'.bo.general.Module-name;');

			}
			
			$roles = array();
			
			foreach ($rs->getRoles() as $qualifiefRoleName) 
			{
				$backEndRole = false;
				$permissions = $rs->getPermissionsByRole($qualifiefRoleName);
				foreach ($permissions as $permission)
				{
					if (!$rs->isFrontEndPermission($permission))
					{
						$backEndRole = true;
						break;
					}
				}
				if ($backEndRole)
				{
					list(, $roleName) = explode('.', $qualifiefRoleName);
					if (!isset($result['roles'][$roleName]))
					{
						$result['roles'][$roleName]  = array('name' => f_Locale::translateUI('&modules.users.bo.dialog.'.$roleName.';'), 'nbperm' => count($permissions), 'used' => 1);
					} 
					else if ($result['roles'][$roleName]['nbperm'] < count($permissions))
					{
						$result['roles'][$roleName]['nbperm'] = count($permissions);
						$result['roles'][$roleName]['used'] += 1;
					}
					else
					{
						$result['roles'][$roleName]['used'] += 1;
					}
					$roles[$roleName] = 1;
				}			
			}
			
			if ($addRolesDefinition)
			{
				$result['modules'][$moduleName]['roles'] = $roles;
			}
		}
		
		uasort($result['modules'], array(__CLASS__, "sortModule"));
		uasort($result['roles'], array(__CLASS__, "sortRole"));
		
		if ($type == 'group')
		{
			$query = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl')->add(Restrictions::eq('group.id', $accessor->getId()));
		}
		else
		{
			$query = $this->getPersistentProvider()->createQuery('modules_generic/userAcl')->add(Restrictions::eq('user.id', $accessor->getId()));
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
	
	public static function sortRole($a, $b)
	{
		$al = $a['nbperm'];
        $bl = $b['nbperm'];
        if ($al == $bl) {
            return 0;
        }
        return ($al < $bl) ? +1 : -1;	
	}
}