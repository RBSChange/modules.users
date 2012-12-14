<?php
/**
 * @package modules.users.persistentdocument.import
 */
class users_PermissionsScriptDocumentElement extends import_ScriptBaseElement
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function setPermissions($document)
	{
		$moduleNames = array();
		$children = array();
		foreach ($this->script->getChildren($this) as $child)
		{
			if ($child instanceof users_PermissionScriptDocumentElement)
			{
				$moduleNames[] = $child->getModuleName();
				$children[] = $child;
			}
			elseif ($child instanceof users_BackendPermissionScriptDocumentElement)
			{
				$moduleNames[] = $child->getModuleName();
				$children[] = $child;
			}
			elseif ($child instanceof users_FrontendPermissionScriptDocumentElement)
			{
				$moduleNames[] = $child->getModuleName();
				$children[] = $child;
			}
		}
		$moduleNames = array_unique($moduleNames);
		
		// Handle inheritance.
		if (isset($this->attributes['inherits']) && $this->attributes['inherits'] == 'true')
		{
			foreach ($moduleNames as $moduleName)
			{
				users_ModuleService::getInstance()->replicateACLsFromAncestorDefinitionPoint($document->getId(), $moduleName);
			}
		}
		
		// Handle permissions.
		foreach ($children as $child)
		{
			$child->setPermission($document);
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