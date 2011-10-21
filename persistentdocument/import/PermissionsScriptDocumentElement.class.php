<?php
/**
 * @package modules.users.persistentdocument.import
 */
class users_PermissionsScriptDocumentElement extends import_ScriptBaseElement
{
    /**
     * @return void
     */
	public function setPermissions($document)
	{		
		$children = $this->script->getChildren($this);
		
		foreach ($children as $child)
		{
			if ($child instanceof users_PermissionScriptDocumentElement)
			{
				$child->setPermission($document);
			}
			elseif ($child instanceof users_BackendPermissionScriptDocumentElement)
			{
				$child->setPermission($document);
			}
			elseif ($child instanceof users_FrontendPermissionScriptDocumentElement)
			{
				$child->setPermission($document);
			}
		}
	}
	
	/**
	 * @return void
	 */
	public function endProcess()
	{
		$this->setPermissions($this->getParent()->getPersistentDocument());
	}
}