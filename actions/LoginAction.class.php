<?php
class users_LoginAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		if (!RequestContext::getInstance()->inHTTPS() && DEFAULT_UI_PROTOCOL === 'https')
		{
			change_Controller::getInstance()->redirectToUrl(LinkHelper::getUIActionLink('users', 'Login')->getUrl());
			return null;
		}
		return change_View::INPUT;
	}
	
	/**
	 * @return string
	 */
	public function getDefaultView()
	{
		return change_View::INPUT;
	}
	
	/**
	 * @return string
	 */
	public function handleError()
	{
		return change_View::INPUT;
	}
	
	/**
	 * @return boolean
	 */
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
			$us = users_UserService::getInstance();
			$user = $us->getIdentifiedBackendUser($login, $password);
			if ($user !== null)
			{
				$us->authenticateBackEndUser($user);
				
				// Clears the request parameters: 1) keep the ones we need
				$pageRef = $request->getParameter(K::PAGE_REF_ACCESSOR);
				$module = $request->getParameter('module');
				$action = $request->getParameter('action');
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
				if (!is_null($access))
				{
					$request->setParameter('access', $access);
				}
				$request->setParameter(K::PAGE_REF_ACCESSOR, $pageRef);
				$request->setParameter('module', $module);
				$request->setParameter('action', $action);
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
		
		if (!empty($errors))
		{
			$request->setAttribute('errors', $errors);
			return false;
		}
		
		return true;
	}
	
	/**
	 * @return string
	 */
	public function getRequestMethods()
	{
		return change_Request::POST;
	}
	
	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
}