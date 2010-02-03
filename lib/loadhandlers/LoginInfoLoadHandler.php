<?php
class users_LoginInfoLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	public function execute($request, $response)
	{
		$httpRequest = $this->getHTTPRequest();
		if (f_util_StringUtils::isEmpty($request->getParameter("login")) &&
		f_util_StringUtils::isEmpty($request->getParameter("password")) &&
		$httpRequest->hasCookie(users_BlockAdminauthenticationAction::LOGIN_INFO_KEY))
		{
			$loginInfo = JsonService::getInstance()->decode($httpRequest->getCookie(users_BlockAdminauthenticationAction::LOGIN_INFO_KEY));
			if (is_array($loginInfo))
			{
				$request->setAttribute("login", $loginInfo["login"]);
				$request->setAttribute("password", $loginInfo["password"]);
				$request->setAttribute("rememberMe", "true");
			}
			else
			{
				Framework::warn("Error while decoding ".users_BlockAdminauthenticationAction::LOGIN_INFO_KEY." cookie");
				$httpRequest->removeCookie(users_BlockAdminauthenticationAction::LOGIN_INFO_KEY);
			}
		}
	}
}
