<?php
class users_BlockShowinfoAction extends block_BlockAction
{

 	/**
	 * @param block_BlockContext $context
	 * @param block_BlockRequest $request
	 * @return String the view name
	 */
    public function execute($context, $request)
    {
    	$user = $context->getUser();
    	
    	$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();

    	$date = date_Calendar::getInstance();
    	$user->setAttribute('LastTime', $date->toString());

    	$params = array();
    	foreach ($user->getAttributeNamespaces() as $namespace)
    	{
    		foreach ($user->getAttributeNames($namespace) as $name)
		    {
		    	$params[$name] = $user->getAttribute($name, $namespace);
		    }
    	}

    	$this->setParameter('currentUserParam', $params);
    	if (is_null($currentUser))
    	{
    		$this->setParameter('anonymousUser', true);
    	}
    	else
    	{
    		$this->setParameter('currentUser', $currentUser);
    	}
    	return block_BlockView::SUCCESS;
    }
}