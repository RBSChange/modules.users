<?php
/**
 * @author inthause
 * @package modules.users
 */
class users_BlockAuthenticationAction extends website_BlockAction
{
	/**
	 * @var users_persistentodcument_user
	 */
	private $currentUser;
	
	/**
	 * @return users_persistentodcument_user
	 */
	protected final function getCurrentUser()
	{
		return $this->currentUser;
	}

	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$request->setAttribute('allowAutoLogin', users_ModuleService::getInstance()->allowAutoLogin());
		if ($this->isInBackoffice())
		{
			return website_BlockView::INPUT;
		}
		$errorlocation = LinkHelper::getDocumentUrl($this->getContext()->getPersistentPage());
		$request->setAttribute('errorlocation', $errorlocation);
		
		$location = change_Controller::getInstance()->getStorage()->readForUser('users_illegalAccessPage');
		if (f_util_StringUtils::isEmpty($location))
		{
			$location = $this->getConfiguration()->getConfigurationParameter("illegalAccessPage");
			if (f_util_StringUtils::isEmpty($location))
			{
				$location = $errorlocation;
			}
		}
		else
		{
			change_Controller::getInstance()->getStorage()->removeForUser('users_illegalAccessPage');
		}
		$request->setAttribute('location', $location);
		
		if ($request->hasParameter('hideRegistrationLinks') && $request->getParameter('hideRegistrationLinks') == 'true')
		{
			$request->setAttribute('hideRegistrationLinks', true);
		}
		
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user !== null)
		{
			$request->setAttribute('user', $user);	
			return website_BlockView::SUCCESS;
		}
		
		$storageId = $this->getContext()->getId() . '_' . $this->getBlockId();
		$request->setAttribute('storageId', $storageId);
		
		$data = change_Controller::getInstance()->getStorage()->read($storageId);
		if (is_array($data))
		{
			foreach ($data as $name => $value) 
			{
				$request->setAttribute($name, $value);
			}
			change_Controller::getInstance()->getStorage()->remove($storageId);
		}
		
		$request->setAttribute('authenticateUrl', LinkHelper::getActionUrl('users', 'Authenticate'));
		return website_BlockView::INPUT;
	}
}