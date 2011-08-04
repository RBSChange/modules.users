<?php
class users_LogoutAction extends change_JSONAction
{
    /**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		users_UserService::getInstance()->authenticateBackEndUser(null);
		
		// Clear all parameters to continue on cleared app
		$request->clearParameters();
		$request->setParameter('access', 'back');
		
		return $this->sendJSON(array('disconected' => TRUE));
    }
}