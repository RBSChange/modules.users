<?php
class users_BlockEditprofilAction extends block_BlockAction
{

    /**
     * @param block_BlockContext $context
     * @param block_BlockRequest $request
     * @return String the view name
     */
    public function execute ($context, $request)
    {
		$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($currentUser === NULL)
		{
		    return block_BlockView::NONE;
		}
		
		$this->setParameter('currentUser', $currentUser);
		$errors = array();
		
		if ($request->hasParameter('submit'))
		{
		    $this->setProperties($currentUser, $request);
			if (!$currentUser->isValid())
			{
				$errors = $this->localizeErrorField($currentUser->getValidationErrors());
			}
			else
			{
                try
                {
                    $currentUser->save();
                    return block_BlockView::SUCCESS;
                } 
                catch (Exception $e)
                {
                    Framework::exception($e);
                    $errors[] = f_Locale::translate('&modules.users.frontoffice.editprofil.exception;');
                }
			}
		}
		
		if (count($errors) > 0)
		{
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
    
    private function setProperties($currentUser, $request)
    {
        $documentModel = $currentUser->getPersistentModel();
		$values = array();
		foreach ($request->getParameters() as $name => $value)
		{
			$property = $documentModel->getProperty($name);
			if ($property == null)
			{
				continue;
			}
			$values[$name] = $value;
		}
		DocumentHelper::setPropertiesTo($values, $currentUser);
    }
    
    private function localizeErrorField($errors)
    {
        $result = array();
        foreach ($errors as $error) 
        {
        	if (strpos($error, 'email') !== false)
        	{
        	    $error = str_replace('email', f_Locale::translate('&modules.users.document.frontenduser.Email;'), $error);
        	} 
        	else if (strpos($error, 'firstname') !== false)
        	{
        	    $error = str_replace('firstname', f_Locale::translate('&modules.users.document.frontenduser.Firstname;'), $error);
        	}
            else if (strpos($error, 'lastname') !== false)
        	{
       	        $error = str_replace('lastname', f_Locale::translate('&modules.users.document.frontenduser.Lastname;'), $error);
        	}
        	$result[] = $error;
        }
        return $result;
    }
}