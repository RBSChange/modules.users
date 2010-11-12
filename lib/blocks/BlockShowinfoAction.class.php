<?php
class users_BlockShowinfoAction extends website_BlockAction
{

	/**
	 * @see f_mvc_Action::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
    {
    	    	
    	$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();

    	$date = date_Calendar::getInstance();
    	$request->setAttribute('LastTime', $date->toString());
    	$request->setAttribute('currentUserParam', array());
    	if (is_null($currentUser))
    	{
    		$request->setAttribute('anonymousUser', true);
    	}
    	else
    	{
    		$request->setAttribute('currentUser', $currentUser);
    	}
    	return website_BlockView::SUCCESS;
    }
}