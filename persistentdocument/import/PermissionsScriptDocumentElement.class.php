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
		if ($document instanceof website_persistentdocument_website)
		{
			$website = $document;
		}
		else 
		{
			$website = f_util_ArrayUtils::firstElement($document->getDocumentService()->getAncestorsOf($document, 'modules_website/website'));
		}
				
		$children = $this->script->getChildren($this);
		foreach ($children as $child)
		{
			if ($child instanceof users_BackendPermissionScriptDocumentElement)
			{
				$child->setPermission($document);
			}
			else if ($child instanceof users_FrontendPermissionScriptDocumentElement)
			{
				if ($website instanceof website_persistentdocument_website)
				{
					$child->setPermission($document, $website);
				}
				else 
				{
					Framework::warn(__METHOD__ . ' no website found.');
				}
			}
		}
	}
}