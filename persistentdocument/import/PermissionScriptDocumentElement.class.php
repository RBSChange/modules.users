<?php
/**
 * @package modules.users.persistentdocument.import
 */
class users_PermissionScriptDocumentElement extends import_ScriptBaseElement
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
    public function setPermission($document)
	{
		if (isset($this->attributes['module']) && isset($this->attributes['role']))
		{
			$roleName = 'modules_'.$this->attributes['module'].'.'.$this->attributes['role'];
			$group = $this->getComputedAttribute('group');
			
			if ($group instanceof users_persistentdocument_group) 
			{
				change_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array($document->getId()));
			}
			elseif (is_array($group))
			{
				foreach ($group as $doc) 
				{
					change_PermissionService::getInstance()->addRoleToGroup($doc, $roleName, array($document->getId()));
				}
			}
		
			$user = $this->getComputedAttribute('user');
			if ($user instanceof users_persistentdocument_user) 
			{
				change_PermissionService::getInstance()->addRoleToUser($user, $roleName, array($document->getId()));
			}
			elseif (is_array($user))
			{
				foreach ($user as $doc) 
				{
					change_PermissionService::getInstance()->addRoleToUser($doc, $roleName, array($document->getId()));
				}
			}
		}
		else
		{
			Framework::warn(__METHOD__ . ' No role defined.');
		}
	}
}