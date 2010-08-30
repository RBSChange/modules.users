<?php
/**
 * @author intportg
 * @package modules.users
 */
class users_RefreshDynamicfrontendgroupAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$group = $this->getDocumentInstanceFromRequest($request);
		$group->getDocumentService()->refresh($group);
		$this->logAction($group);
		return $this->sendJSON(array('id' => $group->getId(), 'modelName' => $group->getDocumentModelName()));
	}
}