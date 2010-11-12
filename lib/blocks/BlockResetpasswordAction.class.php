<?php
class users_BlockResetpasswordAction extends website_BlockAction
{

	private function getUserService()
	{
		return users_UserService::getInstance();
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{	
		if ($this->isInBackoffice())
		{
			return website_BlockView::INPUT;
		}
        if ($request->hasParameter('submit'))
		{
            $errors = array();
            $login = trim($request->getParameter('login'));
            
            if (!empty($login))
            {
                $us = users_FrontenduserService::getInstance();
                $website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
                try 
                {
                	$us->prepareNewPassword($login, $website->getId());
                }
                catch (BaseException $e)
                {
                	if ($e->getKey())
                	{	
                		 $errors[] = f_Locale::translate('&' . $e->getKey() . ';', $e->getAttributes()); 		
                	}
                	else
                	{
                		$errors[] = $e->getMessage();
                	}         
                }
                catch (Exception $e)
                {
					Framework::exception($e);
                    $errors[] = f_Locale::translate('&modules.users.frontoffice.resetpassword.Exception;');                	
                }
            }
            else
            {
                $errors[] = f_Locale::translate('&modules.users.frontoffice.resetpassword.emptylogin;');
            }
            
            if (count($errors) > 0)
            {
            	$request->setAttribute('errors', $errors);
            }
            return website_BlockView::SUCCESS;
        }
        return website_BlockView::INPUT;
	}
}