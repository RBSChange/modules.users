<?php
/**
 * users_SaveProfilesAction
 * @package modules.users.actions
 */
class users_SaveProfilesAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 * @return string
	 */
	public function _execute($context, $request)
	{
		$accessor = $this->getDocumentInstanceFromRequest($request);
		$sectionsStr = $request->getParameter('sections');
		if (!empty($sectionsStr))
		{
			$sections = JsonService::getInstance()->decode($sectionsStr);
			$up = users_ProfileService::getInstance();
			$sectionlist = $up->getProfileNames();
			foreach ($sectionlist as $profileName) 
			{
				if (!isset($sections[$profileName])) {continue;}
				
				unset($sections[$profileName]['id']);
				
				if (count($sections[$profileName]) == 0) {continue;}
				
				$p = $up->getByAccessorIdAndName($accessor->getId(), $profileName);
				if ($p === null)
				{
					$p = $up->getNewDocumentInstanceByName($profileName);
					$p->setAccessor($accessor);
				}
				uixul_DocumentEditorService::getInstance()->importFieldsData($p, $sections[$profileName]);
				$p->save();
			}
		}
		$this->logAction($accessor);
		// Write your code here to set content in $result.
		change_Controller::getInstance()->forward('users', 'LoadProfiles');
		return change_View::NONE;
	}	
}