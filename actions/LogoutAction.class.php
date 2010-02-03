<?php
class users_LogoutAction extends f_action_BaseJSONAction
{
    /**
	 * @param Context $context
	 * @param Request $request
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