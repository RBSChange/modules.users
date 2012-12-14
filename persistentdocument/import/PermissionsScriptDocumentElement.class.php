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
		$children = $this->script->getChildren($this);
		$moduleNames = array();
		$boChildren = array();
		$foChildren = array();
		foreach ($children as $child)
		{
			if ($child instanceof users_BackendPermissionScriptDocumentElement)
			{
				$moduleNames[] = $child->getModuleName();
				$boChildren[] = $child;
			}
			else if ($child instanceof users_FrontendPermissionScriptDocumentElement)
			{
				$moduleNames[] = $child->getModuleName();
				$foChildren[] = $child;
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
		
		// Handle BO permussions.
		foreach ($boChildren as $child)
		{
			$child->setPermission($document);
		}
		
		// Handle FO permissions.
		if (count($foChildren))
		{
			if ($document instanceof website_persistentdocument_website)
			{
				$website = $document;
			}
			else
			{
				$website = f_util_ArrayUtils::firstElement($document->getDocumentService()->getAncestorsOf($document, 'modules_website/website'));
			}
			
			foreach ($foChildren as $child)
			{
				$child->setPermission($document, $website);
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