<?php
class users_RemoveUsersAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$parentId = $request->getParameter(K::PARENT_ID_ACCESSOR);
		$parentDoc = DocumentHelper::getDocumentInstance($parentId, 'modules_users/group');
		if ($parentDoc->getIsdefault())
		{
			throw new BaseException("Can't remove from default group", 'modules.users.errors.Cant-remove-from-default-group');
		}
		
		$docIds = $this->getDocumentIdArrayFromRequest($request);
		foreach ($docIds as $docId)
		{
			$doc = DocumentHelper::getDocumentInstance($docId, 'modules_users/user');	
			$userLabel = $doc->getLabel();
			$parentDoc->removeUserInverse($doc);
			$parentDoc->save();
			$this->logAction($parentDoc, array('userid' => $docId, 'userlabel' => $userLabel));
		}
		return $this->sendJSON(array());
	}
}