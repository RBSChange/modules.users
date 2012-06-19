<?php
/**
 * users_GroupScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_GroupScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return users_persistentdocument_group
	 */
	protected function initPersistentDocument()
	{
		if (isset($this->attributes['byWebsite-refid']))
		{
			$website = $this->getComputedAttribute('byWebsite');
			if ($website && $website->getGroup())
			{
				return  $website->getGroup();
			}
			else
			{
				throw new Exception('Invalid byWebsite-refid: ' . $this->attributes['byWebsite-refid']);
			}
			unset($this->attributes['byWebsite-refid']);
		}
		return users_GroupService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return users_persistentdocument_groupmodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_users/group');
	}
	
	/**
	 * @see import_ScriptDocumentElement::getDocumentProperties()
	 *
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$props = parent::getDocumentProperties();
		if (isset($props['isdefault']))
		{
			unset($props['isdefault']);
		}
		return $props;
	}	
	
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function getParentInTree()
	{
		$pid = ModuleService::getInstance()->getRootFolderId('users');
		return DocumentHelper::getDocumentInstance($pid);
	}
}