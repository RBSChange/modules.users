<?php
class users_BlockChangepasswordAction extends block_BlockAction
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
		if ($context->inBackofficeMode())
		{
			return block_BlockView::INPUT;
		}
		$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($currentUser === NULL)
		{
		    return block_BlockView::NONE;
		}
		$this->setParameter('currentUser', $currentUser);
		
		$errors = array();
		if ($request->hasParameter('submit'))
		{
		   $oldpassword = trim($request->getParameter('oldpassword'));
		   $password = trim($request->getParameter('password'));
		   $confirmpassword = trim($request->getParameter('confirmpassword'));
		   if (!empty($oldpassword) && !empty($password)  && !empty($confirmpassword))
		   {
		      if ($password == $confirmpassword)
		      {	        
		        $us = users_UserService::getInstance();
		        if ($us->checkIdentity($currentUser, $oldpassword))
		        {
		            try
		            {
		                $currentUser->setPassword($password);
		                $currentUser->save();
		                return  block_BlockView::SUCCESS; 
		            }
		            catch (Exception $e)
		            {
		                Framework::exception($e);
		                $errors[] = f_Locale::translate('&modules.users.frontoffice.changepassword.exception;');  
		            }
		        }
                else
                {
                    $errors[] = f_Locale::translate('&modules.users.frontoffice.changepassword.invalidoldpassword;');
                }
		      }
		      else
		      {
		          $errors[] = f_Locale::translate('&modules.users.frontoffice.changepassword.notconfirmpassword;');
		      }
		   }
		   else
		   {
		        $errors[] = f_Locale::translate('&modules.users.frontoffice.changepassword.emptypassword;');
		   }
		}
		if (count($errors) > 0)
		{
		     $this->setParameter('errors', $errors);
		}
		return block_BlockView::INPUT;  
	}
}