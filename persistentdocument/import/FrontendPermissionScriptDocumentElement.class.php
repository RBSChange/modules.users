<?php
/**
 * @package modules.users.persistentdocument.import
 */
class users_FrontendPermissionScriptDocumentElement extends import_ScriptBaseElement
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
	 * @param website_persistentdicument_website $document
	 */
	public function setPermission($document)
	{
		if (isset($this->attributes['module']) && isset($this->attributes['role']))
		{
			$roleName = 'modules_' . $this->attributes['module'] . '.' . $this->attributes['role'];
			
			// Handle groups.
			if (isset($this->attributes['group']))
			{
				$group = users_GroupService::getInstance()->getByLabel($this->attributes['group']);
				if ($group instanceof users_persistentdocument_group)
				{
					change_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array($document->getId()));
				}
				else
				{
					Framework::warn(__METHOD__ . ' invalid group ' . $this->attributes['group'] . '".');
				}
			}
			else if (isset($this->attributes['group-refid']))
			{
				$group = $this->script->getElementById($this->attributes['group-refid'], 'import_ScriptObjectElement')->getObject();
				if ($group instanceof users_persistentdocument_group)
				{
					change_PermissionService::getInstance()->addRoleToGroup($group, $roleName, array($document->getId()));
				}
				else
				{
					Framework::warn(__METHOD__ . ' invalid group refid ' . $this->attributes['group-refid'] . '".');
				}
			}
			
			// Handle users.
			if (isset($this->attributes['user']))
			{
				$group = website_WebsiteService::getInstance()->getDefaultWebsite()->getGroup();
				$login = $this->attributes['user'];
				$users = users_UserService::getInstance()->getUsersByLoginAndGroup($login, $group);
				if (count($users) === 1)
				{
					change_PermissionService::getInstance()->addRoleToUser($users[0], $roleName, array($document->getId()));
				}
				else
				{
					Framework::warn(__METHOD__ . ' invalid user ' . $login . '".');
				}
			}
			else if (isset($this->attributes['user-refid']))
			{
				$user = $this->script->getElementById($this->attributes['user-refid'], 'import_ScriptObjectElement')->getObject();
				if ($user instanceof users_persistentdocument_user)
				{
					change_PermissionService::getInstance()->addRoleToUser($user, $roleName, array($document->getId()));
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