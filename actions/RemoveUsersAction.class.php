<?php
class users_RemoveUsersAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$parentId = $request->getParameter('parentref');
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