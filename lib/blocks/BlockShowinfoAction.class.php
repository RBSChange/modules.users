<?php
class users_BlockShowinfoAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$currentUser = users_UserService::getInstance()->getCurrentUser();

		$date = date_Calendar::getInstance();
		$request->setAttribute('LastTime', $date->toString());
		$request->setAttribute('currentUserParam', array());
		if ($currentUser === null)
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