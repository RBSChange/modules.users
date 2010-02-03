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

		$docIds = $this->getDocumentIdArrayFromRequest($request);
		$result = array('deleted' => array(), 'removed' => array(), 'from' => $parentDoc->getLabel(), 'parentid' => $parentId);
			
		foreach ($docIds as $docId)
		{
			$doc = DocumentHelper::getDocumentInstance($docId, 'modules_users/user');	
			$userLabel = $doc->getLabel();
			// Perform the removing or deletion.
			if ($parentDoc->getIsdefault())
			{
				$doc->delete();
				$result['deleted'][] = $docId;
				
			}
			else
			{
				$parentDoc->removeUserInverse($doc);
				$parentDoc->save();
				$result['removed'][] = $docId;
			}
			$this->logAction($parentDoc, array('userid' => $docId, 'userlabel' => $userLabel));
		}
		return $this->sendJSON($result);
	}
}