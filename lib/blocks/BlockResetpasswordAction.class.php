<?php
class users_BlockResetpasswordAction extends block_BlockAction
{

	private function getUserService()
	{
		return users_UserService::getInstance();
	}

	/**
	 * @param block_BlockContext $context
	 * @param block_BlockRequest $request
	 * @return String the view name
	 */
	public function execute($context, $request)
	{		
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
                $this->setParameter('errors', $errors);
            }
            return block_BlockView::SUCCESS;
        }
        return block_BlockView::INPUT;
	}
	
    /**
     * @param block_BlockContext $context
     * @param block_BlockRequest $request
     * @return String the view name
     */
    public function executeBackOffice ($context, $request)
    {
    	return block_BlockView::INPUT;
    }
}