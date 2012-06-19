<?php

class users_GetBackEndUserAccessAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		if ($user)
		{
			$fullAccess = $user->getIsroot();
			
			$result['userinfos'] = array('fullname' => $user->getFullname(),
										 'login' => $user->getLogin(),
										 'email' => $user->getEmail(),
										 'id' => $user->getId(),
										 'root' => $fullAccess,
										 'userPreferences' => null);
			
			$profile = dashboard_DashboardprofileService::getInstance()->getByAccessorId($user->getId());
			if ($profile !== null && $profile->getUserPreferences() != null)
			{
				$result['userPreferences'] = JsonService::getInstance()->decode($profile->getUserPreferences());
			}
			
			foreach (ModuleService::getInstance()->getModulesObj() as $cModule)
			{
				$packageName = $cModule->getFullName();
				$moduleName = $cModule->getName();
				$version = $cModule->getVersion();
				$rootFolderId = $cModule->getRootFolderId();
				$visible = $cModule->isVisible();
	
				if ($visible)
				{
					$menu = $cModule->getCategory();
					$access = $fullAccess || change_PermissionService::getInstance()->hasPermission($user, $packageName . '.Enabled', $rootFolderId);
					$list = $fullAccess || change_PermissionService::getInstance()->hasPermission($user, $packageName . '.List.rootfolder', $rootFolderId);	
				}
				else
				{
					$access = false;
					$list = $fullAccess || change_PermissionService::getInstance()->hasPermission($user, $packageName . '.List.rootfolder', $rootFolderId);
					$menu = '';
				}

				$result[$moduleName] = array('rootfolderid' => $rootFolderId, 
											 'enabled' => true, 'visible' => $visible, 'menu' => $menu,
											 'access' => $access, 'list' => $list, 'version' => $version);	
			}
		}
		return $this->sendJSON($result);
	}
}