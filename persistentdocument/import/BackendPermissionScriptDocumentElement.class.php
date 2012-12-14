<?php
/**
 * @package modules.users.persistentdocument.import
 */
class users_BackendPermissionScriptDocumentElement extends import_ScriptBaseElement
{
	/**
	 * @return string|null
	 */
	public function getModuleName()
	{
		return isset($this->attributes['module']) ? $this->attributes['module'] : null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function setPermission($document)
	{
		if (isset($this->attributes['module']) && isset($this->attributes['role']))
		{
			$roleName = 'modules_' . $this->attributes['module'] . '.' . $this->attributes['role'];
			
			// handle groups.
			if (isset($this->attributes['group']))
			{
				$group = users_BackendgroupService::getInstance()->getByLabel($this->attributes['group']);
				if ($group instanceof users_persistentdocument_backendgroup)
				{
					f_permission_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array($document->getId()));
				}
				else
				{
					Framework::warn(__METHOD__ . ' invalid group "' . $this->attributes['group'] . '".');
				}
			}
			else if (isset($this->attributes['group-refid']))
			{
				$group = $this->script->getElementById($this->attributes['group-refid'], 'import_ScriptObjectElement')->getObject();
				if ($group instanceof users_persistentdocument_backendgroup)
				{
					f_permission_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array($document->getId()));
				}
				else
				{
					Framework::warn(__METHOD__ . ' invalid group refid ' . $this->attributes['group-refid'] . '".');
				}
			}
			
			// handle users.
			if (isset($this->attributes['user']))
			{
				$user = users_BackenduserService::getInstance()->getBackEndUserByLogin($this->attributes['user']);
				if ($user instanceof users_persistentdocument_backenduser)
				{
					f_permission_PermissionService::getInstance()->addRoleToUser($user, $roleName, array($document->getId()));
				}
				else
				{
					Framework::warn(__METHOD__ . ' invalid user "' . $this->attributes['user'] . '".');
				}
			}
			else if (isset($this->attributes['user-refid']))
			{
				$user = $this->script->getElementById($this->attributes['user-refid'], 'import_ScriptObjectElement')->getObject();
				if ($user instanceof users_persistentdocument_backenduser)
				{
					f_permission_PermissionService::getInstance()->addRoleToUser($user, $roleName, array($document->getId()));
				}
				else
				{
					Framework::warn(__METHOD__ . ' invalid user refid ' . $this->attributes['user-refid'] . '".');
				}
			}
		}
		else
		{
			Framework::warn(__METHOD__ . ' No role defined.');
		}
	}
}