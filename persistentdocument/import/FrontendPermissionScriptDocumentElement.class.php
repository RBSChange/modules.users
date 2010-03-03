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
					Framework::warn(__METHOD__ . ' invalid group refid '.$this->attributes['group-refid'].'".');
				}
			}
			
			// Handle users.
			if (isset($this->attributes['user']))
			{
				if (!($website instanceof website_persistentdocument_website))
				{
					Framework::warn(__METHOD__ . ' user identified by login can\'t be found outside from the context of a website! Permission on "'.$this->attributes['user'].'" skipped.');
					return;
				}
				
				$user = users_WebsitefrontenduserService::getInstance()->getFrontendUserByLogin($this->attributes['user'], $website->getId());
				if ($user instanceof users_persistentdocument_frontenduser)
				{
					f_permission_PermissionService::getInstance()->addRoleToUser($user, $roleName, array($document->getId()));
				}
				else 
				{
					Framework::warn(__METHOD__ . ' invalid user '.$this->attributes['user'].'".');
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
					Framework::warn(__METHOD__ . ' invalid user refid '.$this->attributes['user-refid'].'".');
				}
			}
		}
		else
		{
			Framework::warn(__METHOD__ . ' No role defined.');
		}
	}
}