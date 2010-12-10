<?php
/**
 * users_WebsitefrontenduserScriptDocumentElement
 * @package modules.users.persistentdocument.import
 */
class users_WebsitefrontenduserScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return users_persistentdocument_websitefrontenduser
	 */
	protected function initPersistentDocument()
	{
		return users_WebsitefrontenduserService::getInstance()->getNewDocumentInstance();
	}

	protected function saveDocument()
	{
		$website = DocumentHelper::getDocumentInstance($this->getPersistentDocument()->getWebsiteid(), "modules_website/website");
		$document = $this->getPersistentDocument();
		$parent = users_WebsitefrontendgroupService::getInstance()->getDefaultByWebsite($website);
		$parentId = ($parent) ? $parent->getId() : null;
		$document->save($parentId);
		$document->getDocumentService()->activate($document->getId());
	}

	/**
	 * @see import_ScriptDocumentElement::getDocumentProperties()
	 *
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$props = parent::getDocumentProperties();
		$parentDocument = $this->getParentByClassName("users_WebsitefrontendgroupScriptDocumentElement");
		if ($parentDocument !== null)
		{
			$props['websiteid'] = $parentDocument->getPersistentDocument()->getWebsiteId();
		}
		return $props;
	}
	
	/**
	 * @see import_ScriptDocumentElement::getParentInTree()
	 */
	protected function getParentInTree()
	{
		return null;
	}
}