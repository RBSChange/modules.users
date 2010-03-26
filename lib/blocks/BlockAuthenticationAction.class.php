<?php
/**
 * @author inthause
 * @package modules.users
 */
class users_BlockAuthenticationAction extends website_BlockAction
{
	/**
	 * @var users_persistentodcument_frontenduser
	 */
	private $currentUser;
	
	/**
	 * @return users_persistentodcument_frontenduser
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
	public function initialize($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return;
		}

		$us = users_UserService::getInstance();
		$this->currentUser = $us->getCurrentFrontEndUser();
		if ($this->currentUser !== null && $this->findParameterValue('logout'))
		{
			$us->authenticateFrontEndUser(null);
			$this->currentUser = null;
			users_ModuleService::getInstance()->unsetAutoLogin();
			$this->redirectToUrl(website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getUrl());
		}
		else 
		{
			$login = $this->findParameterValue('login');
			$password = $this->findParameterValue('password');
			if ($login && $password && $this->findParameterValue('submit'))
			{
				$websiteId = website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getId();
				$user = $us->getIdentifiedFrontendUser($login, $password, $websiteId);
				if ($user !== null)
				{
					$this->currentUser = $user;
					$us->authenticateFrontEndUser($this->currentUser);
					$autoLogin = $this->findParameterValue('autoLogin');
					if ($autoLogin === 'yes')
					{
						users_ModuleService::getInstance()->setAutoLogin($user);
					}					
					
					$agaviUser = Controller::getInstance()->getContext()->getUser();
					$illegalAccessPage = $agaviUser->getAttribute('illegalAccessPage');
					if ($illegalAccessPage)
					{
						$agaviUser->setAttribute('illegalAccessPage', null);
						$this->redirectToUrl($illegalAccessPage);
					}
					else
					{
					    try 
					    {
					        $page = TagService::getInstance()->getDocumentByContextualTag('contextual_website_website_modules_users_secure-homepage', 
					            website_WebsiteModuleService::getInstance()->getCurrentWebsite());    
					        $this->redirectToUrl(LinkHelper::getDocumentUrl($page));
					    }
					    catch (TagException $e)
					    {
					        Framework::info($e->getMessage());
					    }
					}
				}
				else
				{
					$errors = array();
					$errors[] = f_Locale::translate('&modules.users.frontoffice.authentication.BadAuthentication;');
					$request->setAttribute('errors', $errors);
				}
			}
		}
	}

	/**
	 * @see f_mvc_Action::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}

		if ($this->currentUser !== null)
		{
			$request->setAttribute('currentUser', $this->currentUser);
			$request->setAttribute('logoutUrl', LinkHelper::getCurrentUrl(array($this->getModuleName().'Param[logout]' => 'logout')));
			return website_BlockView::SUCCESS;
		}
		
		$request->setAttribute('allowAutoLogin', users_ModuleService::getInstance()->allowAutoLogin());
		return website_BlockView::INPUT;
	}
}