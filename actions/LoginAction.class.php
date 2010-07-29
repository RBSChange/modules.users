<?php
class users_LoginAction extends users_ActionBase
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		if (!RequestContext::getInstance()->inHTTPS() && DEFAULT_UI_PROTOCOL === 'https')
		{
			Controller::getInstance()->redirectToUrl(LinkHelper::getUIActionLink('users', 'Login')->getUrl());
			return null;
		}
		return View::INPUT;
	}
	
	public function getDefaultView()
	{
		return View::INPUT;
	}
	
	public function handleError()
	{
		return View::INPUT;
	}
	
	public function validate()
	{
		$context = $this->getContext();
		$request = $context->getRequest();
		
		$login = trim($request->getParameter('login'));
		$password = trim($request->getParameter('password'));
		
		$errors = array();
		if (empty($login) || empty($password))
		{
			$errors[] = '&modules.users.messages.error.LoginAndPasswordRequired;';
		}
		else
		{
			$us = $this->getUserService();
			$user = $us->getIdentifiedBackendUser($login, $password);
			if ($user !== null)
			{
				$us->authenticateBackEndUser($user);
					
				// Clears the request parameters: 1) keep the ones we need
				$pageRef = $request->getParameter(K::PAGE_REF_ACCESSOR);
				$module = $request->getParameter(AG_MODULE_ACCESSOR);
				$action = $request->getParameter(AG_ACTION_ACCESSOR);
				$access = $request->getParameter('access');
				$popup = $request->hasParameter("popup");
				
				$request->setCookie('login', $login);
				if ($popup)
				{
					$request->setCookie('popup', 'on');
				}
				else
				{
					$request->setCookie('popup', 'off');
				}
				
				// 2) Remove all parameters
				$request->clearParameters();
				// 3) Set the desired parameters
				if (! is_null($access))
				{
					$request->setParameter('access', $access);
				}
				$request->setParameter(K::PAGE_REF_ACCESSOR, $pageRef);
				$request->setParameter(AG_MODULE_ACCESSOR, $module);
				$request->setParameter(AG_ACTION_ACCESSOR, $action);
				if ($popup)
				{
					$request->setParameter("popup", "on");
				}
			
			}
			else
			{
				$errors[] = '&modules.users.messages.error.BadAuthentication;';
			}
		}
		
		if (! empty($errors))
		{
			$request->setAttribute('errors', $errors);
			return false;
		}
		
		return true;
	}
	
	public function getRequestMethods()
	{
		return Request::POST;
	}
	
	public function isSecure()
	{
		return false;
	}
}
