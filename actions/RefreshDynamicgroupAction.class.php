<?php
/**
 * @package modules.users
 */
class users_RefreshDynamicgroupAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$group = $this->getDocumentInstanceFromRequest($request);
		$group->getDocumentService()->refresh($group);
		$this->logAction($group);
		return $this->sendJSON(array('id' => $group->getId(), 'modelName' => $group->getDocumentModelName()));
	}
}