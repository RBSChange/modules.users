<?php
/**
 * @package modules.users.persistentdocument.import
 */
class users_FrontendPermissionScriptDocumentElement extends import_ScriptBaseElement
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param website_persistentdicument_website $document
	 */
    public function setPermission($document, $website)
	{
		if (isset($this->attributes['module']) && isset($this->attributes['role']))
		{
			$roleName = 'modules_'.$this->attributes['module'].'.'.$this->attributes['role'];
			
			// Handle groups.
			if (isset($this->attributes['group']))
			{
				$group = users_WebsitefrontendgroupService::getInstance()->getByLabel($this->attributes['group']);
				if ($group instanceof users_persistentdocument_frontendgroup)
				{
					f_permission_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array($document->getId()));
				}
				else 
				{
					Framework::warn(__METHOD__ . ' invalid group '.$this->attributes['group'].'".');
				}
			}
			else if (isset($this->attributes['group-refid']))
			{
				$group = $this->script->getElementById($this->attributes['group-refid'], 'import_ScriptObjectElement')->getObject();
				if ($group instanceof users_persistentdocument_frontendgroup)
				{
					f_permission_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array($document->getId()));
				}
				else 
				{
					Framework::warn(__METHOD__ . ' invalid group '.$this->attributes['group'].'".');
				}
			}
			
			// Handle users.
			if (isset($this->attributes['user']))
			{
				$user = users_WebsitefrontenduserService::getInstance()->getFrontendUserByLogin($this->attributes['user'], $website->getId());
				if ($user instanceof users_persistentdocument_frontenduser)
				{
					f_permission_PermissionService::getInstance()->addRoleToUser($user, $roleName, array($document->getId()));
				}
				else 
				{
					Framework::warn(__METHOD__ . ' invalid user '.$this->attributes['users'].'".');
				}
			}
			else if (isset($this->attributes['user-refid']))
			{
				$user = $this->script->getElementById($this->attributes['user-refid'], 'import_ScriptObjectElement')->getObject();
				if ($user instanceof users_persistentdocument_frontenduser)
				{
					f_permission_PermissionService::getInstance()->addRoleToUser($user, $roleName, array($document->getId()));
				}
				else 
				{
					Framework::warn(__METHOD__ . ' invalid user '.$this->attributes['users'].'".');
				}
			}
		}
		else
		{
			Framework::warn(__METHOD__ . ' No role defined.');
		}
	}
}