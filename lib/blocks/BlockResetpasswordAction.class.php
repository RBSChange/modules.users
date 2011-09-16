<?php
class users_BlockResetpasswordAction extends block_BlockAction
{
	/**
	 * @param block_BlockContext $context
	 * @param block_BlockRequest $request
	 * @return String the view name
	 */
	public function execute($context, $request)
	{		
		// FIX #42282: use forBlockName to resolve conflicts between Resetpassword and Authentication...
        if ($request->hasParameter('submit') && (!$request->hasNonEmptyParameter('forBlockName') || $request->getParameter('forBlockName') == 'Resetpassword'))
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
					$errors[] = $e->getLocaleMessage();
				}
				$this->setParameter("loginAsHtml", f_util_HtmlUtils::textToHtml($login));
            }
            else
            {
                $errors[] = f_Locale::translate('&modules.users.frontoffice.resetpassword.emptylogin;');
            }
            
            if (count($errors) == 0)
            {
                return block_BlockView::SUCCESS;
            }
            $this->setParameter('errors', $errors);
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