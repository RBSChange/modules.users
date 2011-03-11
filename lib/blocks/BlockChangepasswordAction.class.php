<?php
class users_BlockChangepasswordAction extends website_BlockAction
{
	private function getUserService()
	{
		return users_UserService::getInstance();
	}

	/**
	 * @see f_mvc_Action::execute()
	 *
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
		$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($currentUser === NULL)
		{
		    return website_BlockView::NONE;
		}
		$request->setAttribute('currentUser', $currentUser);
		
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
			    $tm = f_persistentdocument_TransactionManager::getInstance();

		            try
		            {
                                $tm->beginTransaction();
		                $currentUser->setPassword($password);
		                $currentUser->save();
                                $tm->commit();
		                return  website_BlockView::SUCCESS; 
		            }
		            catch (Exception $e)
		            {
		                Framework::exception($e);
                                $tm->rollBack($e);
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
		     $request->setAttribute('errors', $errors);
		}
		return website_BlockView::INPUT;  
	}
}
