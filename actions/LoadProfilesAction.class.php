<?php
/**
 * users_LoadProfilesAction
 * @package modules.users.actions
 */
class users_LoadProfilesAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 * @return string
	 */
	public function _execute($context, $request)
	{
		$accessor = $this->getDocumentInstanceFromRequest($request);		
		$result = array('id' => $accessor->getId(), 'lang' => $accessor->getLang(), 'documentversion' => $accessor->getDocumentversion());
		$up = users_ProfileService::getInstance();		
		$allowedProperties = array('id');
		$result['sectionlist'] = $up->getProfileNames();
		foreach ($result['sectionlist'] as $profileName) 
		{
			$p = $up->getByAccessorIdAndName($accessor->getId(), $profileName);
			if ($p === null)
			{
				$p = $up->getNewDocumentInstanceByName($profileName);
			}
			$result['sections'][$profileName] = uixul_DocumentEditorService::getInstance()->exportFieldsData($p, $allowedProperties, $accessor->getId());
		}
		return $this->sendJSON($result);
	}
}